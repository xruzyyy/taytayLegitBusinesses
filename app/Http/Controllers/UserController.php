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

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        $unseenCount = DB::table('ch_messages')->where('to_id', '=', Auth::user()->id)->where('seen', '=', '0')->count();
        return view('users.index', compact('users', 'unseenCount'));
    }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            // Handle form submission and create new user

            // Validate the incoming request
            $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6',
                'type' => 'required|in:user,admin,business',
                // Add more validation rules as needed
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
                'email_verified_at' => now(), // Set email_verified_at to current timestamp
                'profile_image' => $profileImageName ? 'uploads/profile_images/' . $profileImageName : null, // Use profile image if available
                'status' => in_array($request->input('type'), ['user', 'admin', 'business']) ? 1 : 0,
                'is_active' => in_array($request->input('type'), ['user', 'admin', 'business']) ? 1 : 0,
                'type' => in_array($request->input('type'), ['user', 'admin']) ? 0 : 2, // Map user type string to integer value
                'account_expiration_date' => $request['type'] === 'business' ? $expirationDate : null,

            ]);

            // Trigger events and notifications

            // Redirect the user to the index page of users
            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } else {
            // Show the form to create a new user
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
            'password' => 'required|string|min:6',
            'type' => 'required|in:user,admin,business',
            // Add more validation rules as needed
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
            'email_verified_at' => now(), // Set email_verified_at to current timestamp
            'status' => in_array($request->input('type'), ['user', 'admin', 'business']) ? 1 : 0,
            'is_active' => in_array($request->input('type'), ['user', 'admin', 'business']) ? 1 : 0,
            'type' => in_array($request->input('type'), ['user', 'admin']) ? 0 : 2, // Map user type string to integer value
            'account_expiration_date' => $request['type'] === 'business' ? $expirationDate : null,
        ]);

        // Trigger the NewUserRegistered event
        event(new NewUserRegistered($user, $user->type));

        // Send notification to the specific email address
        Notification::route('mail', 'cruzjerome012@gmail.com')
            ->notify(new NewUserNotification($user));

        // Redirect to a relevant page after successful creation
        return redirect ()->route('users.index')->with('success', 'User created successfully');
    }

    public function update(Request $request, $userId)
{
    $user = User::findOrFail($userId);

    // Validate request data
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $userId,
        'type' => 'required|string|in:user,admin,business',
        'status' => 'required|string|in:1_1,0_0',
        'password' => 'nullable|string|min:8|confirmed',
        'account_expiration_date' => 'nullable|date',
    ]);

    // Map user type string to integer value
    $typeMap = [
        'user' => 0,
        'admin' => 1,
        'business' => 2,
    ];

    // Split status into status and is_active
    list($status, $is_active) = explode('_', $request->input('status'));

    // Update user details
    $user->name = $request->input('name');
    $user->email = $request->input('email');
    $user->type = $typeMap[$request->input('type')];
    $user->status = $status;
    $user->is_active = $is_active;

    // Update password if provided
    if ($request->filled('password')) {
        $user->password = Hash::make($request->input('password'));
    }

    // Update account expiration date if provided and user is a business
    if ($request->filled('account_expiration_date') && $request->input('type') === 'business') {
        $user->account_expiration_date = Carbon::parse($request->input('account_expiration_date'));
    } else {
        $user->account_expiration_date = $request->input('account_expiration_date');
    }

    // Save changes
    $user->save();

    return redirect()->route('users')->with('message', 'User updated successfully!');
}



public function toggleStatus(Request $request, $userId)
{
    $user = User::findOrFail($userId);

    // Toggle user status
    $user->is_active = !$user->is_active;

    // Update account expiration date if provided
    if ($request->has('account_expiration_date')) {
        $user->account_expiration_date = Carbon::parse($request->account_expiration_date);
    }

    $user->save();

    return redirect()->route('users.index')->with('success', 'User status updated successfully.');
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
        $query = User::query();

        // Filtering by status
        if ($request->has('filter')) {
            $filterValue = $request->input('filter');
            if ($filterValue === '1' || $filterValue === '0') {
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


