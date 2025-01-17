<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Notifications\AccountUpdatedNotification;
use Illuminate\Support\Facades\Notification;

class UpdateAccountController extends Controller
{
    public function showUpdateForm()
    {
        if (Auth::user()->status == 1 && Auth::user()->type == 'business') {
            return redirect('/business/home')->withErrors('There is Nothing To Update In Your Account!');
        }
        // Fetch rejection details
        $user = Auth::user();
        $rejectionDetails = $user->rejection_details; // Assuming 'rejection_details' is the column name in your users table

        return view('auth.update_account_details', ['rejectionDetails' => $rejectionDetails]);
    }


    public function storeAccountUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,heic|dimensions:min_width=480,min_height=480',
        ], [
            'image.dimensions' => 'The permit image dimensions must be at least 480x480 pixels.',
        ]);

        $user = Auth::user();

        // Handle image upload if required
        if ($request->hasFile('image')) {
            $profileImagePath = 'uploads/profile_images/';
            $profileImageName = time() . '_profile.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->move($profileImagePath, $profileImageName);
            $user->image = $profileImagePath . $profileImageName;
        }

        $user->name = $request->input('name');
        $user->save();

        // Notify admin about the account update
        Notification::route('mail', 'misoutcompany@gmail.com')
            ->notify(new AccountUpdatedNotification($user));

        return redirect()->route('update_account_details')->with('success', 'Account details updated successfully.');
    }
}
