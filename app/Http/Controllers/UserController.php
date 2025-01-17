<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewUserNotification;
use App\Events\NewUserRegistered;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRejected;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Auth\Events\Registered;
use App\Mail\UserStatusChanged;
class UserController extends Controller
{


    public function rejectUser(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $request->validate([
                'rejection_reason' => 'required|string',
            ]);

            $user->rejection_details = $request->input('rejection_reason');
            $user->status = 3;
            $user->is_active = 3;
            $user->type = 2;

            $user->save();

            Mail::to($user->email)->send(new UserRejected($user));

            return redirect()->route('users.index')->with('success', 'User Disabled successfully.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Failed to Disable user: ' . $e->getMessage());
        }
    }
    public function index()
    {
        $users = User::where('type', 2)->get(); // Fetch only users with type 2 (business)
        $unseenCount = DB::table('ch_messages')->where('to_id', '=', Auth::user()->id)->where('seen', '=', '0')->count();
        return view('users.index', compact('users', 'unseenCount'));
    }


    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string',
                    'email' => 'required|email|unique:users',
                    'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
                    'type' => 'required|in:user,admin,business',
                    'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                    'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                $expirationDate = $request['type'] === 'business' ? now()->addYear() : null;

                $profileImageName = null;
                if ($request->hasFile('profile_image')) {
                    $profileImagePath = 'uploads/profile_images/';
                    $profileImageName = time() . '_profile.' . $request->file('profile_image')->getClientOriginalExtension();
                    $request->file('profile_image')->move($profileImagePath, $profileImageName);
                }

                $permitImageName = null;
                if ($request->input('type') !== 'user' && $request->hasFile('image')) {
                    $permitImagePath = 'uploads/permit_images/';
                    $permitImageName = time() . '_permit.' . $request->file('image')->getClientOriginalExtension();
                    $request->file('image')->move($permitImagePath, $permitImageName);
                }

                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'image' => $permitImageName ? 'uploads/permit_images/' . $permitImageName : null,
                    'profile_image' => $profileImageName ? 'uploads/profile_images/' . $profileImageName : null,
                    'status' => in_array($request->input('type'), ['user', 'admin']) ? 1 : 0,
                    'is_active' => in_array($request->input('type'), ['user', 'admin']) ? 1 : 0,
                    'type' => $request->input('type') === 'admin' ? 1 : ($request->input('type') === 'user' ? 0 : 2),
                    'account_expiration_date' => $expirationDate,
                ]);

                $user->sendEmailVerificationNotification();
                event(new NewUserRegistered($user, $user->type));

                return redirect()->route('users.index')->with('success', 'User created successfully and verification email sent.');
            } catch (\Exception $e) {
                return redirect()->route('users.index')->with('error', 'Failed to create user: ' . $e->getMessage());
            }
        } else {
            $users = User::all();
            $unseenCount = DB::table('ch_messages')->where('to_id', '=', Auth::user()->id)->where('seen', '=', '0')->count();
            return view('users.create', compact('users', 'unseenCount'));
        }
    }





    public function edit($userId)
    {
        $user = User::find($userId);
        return view('users.edit', compact('user'));
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
            'type' => 'required|in:user,admin,business',
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif',
        ]);

        // Define expiration date for business type
        $expirationDate = null;
        if ($request['type'] === 'business') {
            $expirationDate = now()->addYear();
        }

        // Handle profile image upload
        $profileImageName = null;
        if ($request->hasFile('profile_image')) {
            $profileImagePath = 'uploads/profile_images/';
            $profileImageName = time() . '_profile.' . $request->file('profile_image')->getClientOriginalExtension();
            $request->file('profile_image')->move($profileImagePath, $profileImageName);
        }

        // Handle permit image upload if required
        $permitImageName = null;
        if ($request->input('type') !== 'user' && $request->hasFile('image')) {
            $permitImagePath = 'uploads/permit_images/';
            $permitImageName = time() . '_permit.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->move($permitImagePath, $permitImageName);
        }

        // Create the user
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'image' => $permitImageName ? 'uploads/permit_images/' . $permitImageName : null, // Use permit image if available
            'profile_image' => $profileImageName ? 'uploads/profile_images/' . $profileImageName : null, // Use profile image if available
            'status' => in_array($request->input('type'), ['user', 'admin']) ? 1 : 0, // Set status to 1 for user and admin, 0 for business
            'is_active' => in_array($request->input('type'), ['user', 'admin']) ? 1 : 0, // Set is_active to 1 for user and admin, 0 for business
            'type' => $request->input('type') === 'admin' ? 1 : ($request->input('type') === 'user' ? 0 : 2), // Map user type string to integer value
            'account_expiration_date' => $request->input('type') === 'business' ? $expirationDate : null,
        ]);


        // Send the email verification notification
        $user->sendEmailVerificationNotification();

        // Trigger the NewUserRegistered event
        event(new NewUserRegistered($user, $user->type));
        // Send notification to the specific email

        // Notification::route('mail', 'misoutcompany@gmail.com')
        //     ->notify(new NewUserNotification($user));

        // Redirect to a relevant page after successful creation
        return redirect()->route('users.index')->with('success', 'User created successfully and verification email sent.');
    }



    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8|confirmed',
                'type' => 'required|string|in:user,admin,business',
                'account_expiration_date' => 'nullable|date',
                'status' => 'required|string',
                'image' => 'nullable|mimes:jpg,jpeg,webp,png,jfif|dimensions:min_width=480,min_height=480',
            ]);

            if ($request->hasFile('image')) {
                $permitImagePath = 'uploads/permit_images/';
                $permitImageName = time() . '_permit.' . $request->file('image')->getClientOriginalExtension();
                $request->file('image')->move($permitImagePath, $permitImageName);

                if ($user->image) {
                    $oldImagePath = public_path($user->image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $user->image = $permitImagePath . $permitImageName;
            }

            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $typeMap = ['user' => 0, 'admin' => 1, 'business' => 2];
            $user->type = $typeMap[$validatedData['type']];

            $user->account_expiration_date = $validatedData['account_expiration_date'];
            list($user->status, $user->is_active) = explode('_', $validatedData['status']);
            $user->save();

            // Send the email verification notification if email is updated
            if ($user->wasChanged('email')) {
                $user->sendEmailVerificationNotification();
            }

            return redirect()->route('users.index')->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }



    public function toggleStatus(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            if ($user->status == 3 || ($user->status == 0 && $user->is_active == 0)) {
                $user->status = 1;
                $user->is_active = 1;
                $statusMessage = 'Your account is activated successfully.';
            } else {
                $user->status = 0;
                $user->is_active = 0;
                $statusMessage = 'Your account is deactivated .';
            }

            if ($request->has('account_expiration_date')) {
                $user->account_expiration_date = Carbon::parse($request->account_expiration_date);
            }

            $user->save();

            // Send email notification
            Mail::to($user->email)->send(new UserStatusChanged($user, $statusMessage));

            return redirect()->route('users.index')->with('success', $statusMessage);
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Failed to update user status: ' . $e->getMessage());
        }
    }



    public function destroy($userId)
    {
        // Logic to delete user
        $user = User::find($userId);
        $user->delete();

        return redirect()->route('users')->with('message', 'User deleted successfully!');
    }

    public function sortTable(Request $request)
    {
        // Initialize query builder for User model
        $query = User::query()->where('type', 2); // Adjust 'type' field based on your actual database column name

        // Filtering by status
        if ($request->has('filter')) {
            $filterValue = $request->input('filter');
            if ($filterValue === '1' || $filterValue === '0' || $filterValue === '3') {
                $query->where('is_active', $filterValue);
            }
        }

        // Sorting
        if ($request->has('sort')) {
            if ($request->input('sort') == 'newest') {
                $query->orderBy('created_at', 'desc');
            } elseif ($request->input('sort') == 'oldest') {
                $query->orderBy('created_at', 'asc');
            }
        }

        // Pagination limit
        $limit = $request->input('limit', 10);

        if ($limit == 'all') {
            $users = $query->get();
        } else {
            $users = $query->paginate($limit)->withQueryString();
        }

        // Fetch unseen message count
        $unseenCount = DB::table('ch_messages')
            ->where('to_id', '=', Auth::user()->id)
            ->where('seen', '=', '0')
            ->count();

        // Pass all necessary variables to the view
        return view('users.index', compact('users', 'unseenCount'));
    }
}
