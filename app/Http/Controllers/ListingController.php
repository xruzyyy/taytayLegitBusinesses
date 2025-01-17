<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Rules\ImageDimensions;
use App\Rules\LocationRequired;
use Carbon\Carbon;

class ListingController extends Controller
{
    /**
     * Display the form for creating a new listing or store the listing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $id)
    {
        // Fetch the authenticated user
        $user = User::findOrFail($id);

        // Check if the user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to create a listing.');
        }

        // Determine if the user is a business
        $isBusiness = ($user->type === 'business');

        // If it's a POST request, handle form submission
        if ($request->isMethod('post')) {
            // Validate the request data, including store hours
            $request->validate([
                'businessName' => 'required|max:255|string',
                'description' => 'required|max:200|string',
                'images.*' => 'required|mimes:jpg,jpeg,webp,png,jfif',
                'type' => 'required',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'contactNumber' => 'required|numeric|digits:11|unique:posts,contactNumber',
                'is_active' => 'sometimes',
                // Store hours fields validation with nullable
                'mondayOpen' => 'nullable|date_format:H:i',
                'mondayClose' => 'nullable|required_with:mondayOpen|date_format:H:i|after:mondayOpen',
                'tuesdayOpen' => 'nullable|date_format:H:i',
                'tuesdayClose' => 'nullable|required_with:tuesdayOpen|date_format:H:i|after:tuesdayOpen',
                'wednesdayOpen' => 'nullable|date_format:H:i',
                'wednesdayClose' => 'nullable|required_with:wednesdayOpen|date_format:H:i|after:wednesdayOpen',
                'thursdayOpen' => 'nullable|date_format:H:i',
                'thursdayClose' => 'nullable|required_with:thursdayOpen|date_format:H:i|after:thursdayOpen',
                'fridayOpen' => 'nullable|date_format:H:i',
                'fridayClose' => 'nullable|required_with:fridayOpen|date_format:H:i|after:fridayOpen',
                'saturdayOpen' => 'nullable|date_format:H:i',
                'saturdayClose' => 'nullable|required_with:saturdayOpen|date_format:H:i|after:saturdayOpen',
                'sundayOpen' => 'nullable|date_format:H:i',
                'sundayClose' => 'nullable|required_with:sundayOpen|date_format:H:i|after:sundayOpen',
            ]);


            // Process the images uploads
            $paths = [];
            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {
                    $extension = $image->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $path = 'uploads/category/';

                    $image->move($path, $filename);
                    $paths[] = $path . $filename;
                }
            }

            // Create the post with the user_id set to the currently authenticated user's ID
            $posts = Posts::create([
                'businessName' => $request->businessName,
                'description' => $request->description,
                'images' => json_encode($paths),
                'contactNumber' => $request->contactNumber,
                'is_active' => $request->has('is_active') ? $request->input('is_active') : 0,
                'type' => $request->type,
                'user_id' => auth()->user()->id,
                // Store store hours fields
                'monday_open' => $request->input('mondayOpen'),
                'monday_close' => $request->input('mondayClose'),
                'tuesday_open' => $request->input('tuesdayOpen'),
                'tuesday_close' => $request->input('tuesdayClose'),
                'wednesday_open' => $request->input('wednesdayOpen'),
                'wednesday_close' => $request->input('wednesdayClose'),
                'thursday_open' => $request->input('thursdayOpen'),
                'thursday_close' => $request->input('thursdayClose'),
                'friday_open' => $request->input('fridayOpen'),
                'friday_close' => $request->input('fridayClose'),
                'saturday_open' => $request->input('saturdayOpen'),
                'saturday_close' => $request->input('saturdayClose'),
                'sunday_open' => $request->input('sundayOpen'),
                'sunday_close' => $request->input('sundayClose'),
            ]);

            // Dispatch the BusinessListingAdded event
            event(new \App\Events\BusinessListingAdded($posts, $request->businessName, auth()->user()->id));

            // Check if the user's status is 0 and update the related category's is_active field to 0
            if ($request->has('is_active') && $request->input('is_active') == 0) {
                User::where('id', auth()->user()->id)
                    ->update(['status' => 0]);

                Posts::where('user_id', auth()->user()->id)
                    ->update(['is_active' => 0]);
            }

            return redirect()->route('listings.create', ['id' => $id])->with('success', 'Listing created successfully!');
        }

        // Fetch unseen message count
        $unseenCount = DB::table('ch_messages')
            ->where('to_id', '=', Auth::user()->id)
            ->where('seen', '=', '0')
            ->count();

        // Fetch the latitude and longitude values from the request (assuming they're passed via query string)
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');

        // Pass the latitude, longitude, and other necessary data to the view
        return view('listings.create', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'unseenCount' => $unseenCount,
            'isBusiness' => $isBusiness,
            'user' => $user,
        ]);
    }

    /**
     * Store a newly created listing in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request data, including store hours
        $request->validate([
            'businessName' => 'required|max:255|string',
            'description' => 'required|max:200|string',
            'images.*' => ['required', 'mimes:jpg,jpeg,webp,png,jfif', new ImageDimensions()],
            'type' => 'required',
            'location' => ['required', new LocationRequired()],
            'contactNumber' => 'required|string|digits_between:10,15',
            'is_active' => 'sometimes',
            // Store hours fields validation with nullable
            'mondayOpen' => 'nullable|date_format:H:i',
            'mondayClose' => 'nullable|required_with:mondayOpen|date_format:H:i|after:mondayOpen',
            'tuesdayOpen' => 'nullable|date_format:H:i',
            'tuesdayClose' => 'nullable|required_with:tuesdayOpen|date_format:H:i|after:tuesdayOpen',
            'wednesdayOpen' => 'nullable|date_format:H:i',
            'wednesdayClose' => 'nullable|required_with:wednesdayOpen|date_format:H:i|after:wednesdayOpen',
            'thursdayOpen' => 'nullable|date_format:H:i',
            'thursdayClose' => 'nullable|required_with:thursdayOpen|date_format:H:i|after:thursdayOpen',
            'fridayOpen' => 'nullable|date_format:H:i',
            'fridayClose' => 'nullable|required_with:fridayOpen|date_format:H:i|after:fridayOpen',
            'saturdayOpen' => 'nullable|date_format:H:i',
            'saturdayClose' => 'nullable|required_with:saturdayOpen|date_format:H:i|after:saturdayOpen',
            'sundayOpen' => 'nullable|date_format:H:i',
            'sundayClose' => 'nullable|required_with:sundayOpen|date_format:H:i|after:sundayOpen',
        ]);

        // Check if the user already has a listing
        $existingListing = Posts::where('user_id', auth()->user()->id)->exists();

        // If the user already has a listing, redirect back with an error message
        if ($existingListing) {
            return redirect()->route('listings.create', ['id' => auth()->user()->id])->with('error', 'You can only create one listing per user.');
        }

        // Process images uploads
        $paths = [];
        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $extension = $image->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $extension;
                $path = 'uploads/category/';

                $image->move($path, $filename);
                $paths[] = $path . $filename;
            }
        }

        // Store the image paths as a JSON string
        $imagesJson = json_encode($paths);

        // Extract latitude and longitude from the location field
        $location = $request->input('location');
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];

        // Create the listing with store hours and other fields
        $post = Posts::create([
            'businessName' => $request->businessName,
            'description' => $request->description,
            'images' => $imagesJson,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'contactNumber' => $request->contactNumber,
            'is_active' => $request->has('is_active') ? $request->input('is_active') : 1,
            'type' => $request->type,
            'user_id' => auth()->user()->id,
            // Store store hours fields
            'monday_open' => $request->input('mondayOpen'),
            'monday_close' => $request->input('mondayClose'),
            'tuesday_open' => $request->input('tuesdayOpen'),
            'tuesday_close' => $request->input('tuesdayClose'),
            'wednesday_open' => $request->input('wednesdayOpen'),
            'wednesday_close' => $request->input('wednesdayClose'),
            'thursday_open' => $request->input('thursdayOpen'),
            'thursday_close' => $request->input('thursdayClose'),
            'friday_open' => $request->input('fridayOpen'),
            'friday_close' => $request->input('fridayClose'),
            'saturday_open' => $request->input('saturdayOpen'),
            'saturday_close' => $request->input('saturdayClose'),
            'sunday_open' => $request->input('sundayOpen'),
            'sunday_close' => $request->input('sundayClose'),
        ]);

        // Dispatch the BusinessListingAdded event if needed
        event(new \App\Events\BusinessListingAdded($post, $request->businessName, auth()->user()->id));

        // Redirect back to business.home with a success message
        return redirect()
            ->route('business.home')
            ->with('success', 'Successfully Created!');
    }

    public function edit($id)
    {
        // Fetch the listing by ID
        $listing = Posts::findOrFail($id);

        // Pass the listing data to the edit view
        return view('listings.edit', ['listing' => $listing]);
    }

    /**
     * Update the specified listing in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'businessName' => 'required|max:255|string',
            'description' => 'required|max:200|string',
            // Add validation rules for other fields as needed
        ]);

        // Fetch the listing by ID
        $listing = Posts::findOrFail($id);

        // Update the listing with the new data
        $listing->businessName = $request->businessName;
        $listing->description = $request->description;
        // Update other fields as needed

        // Save the updated listing
        $listing->save();

        // Redirect back to the edit form with a success message
        return redirect()->route('listings.edit', ['id' => $listing->id])->with('success', 'Listing updated successfully!');
    }


    // Display the map page
    public function createForm()
    {
        // Fetch unseen message count
        $unseenCount = DB::table('ch_messages')
            ->where('to_id', '=', Auth::user()->id)
            ->where('seen', '=', '0')
            ->count();

        return view('listings.createForm', ['unseenCount' => $unseenCount]);
    }

    // Display the map page
    public function map()
    {
        // Fetch unseen message count
        $unseenCount = DB::table('ch_messages')
            ->where('to_id', '=', Auth::user()->id)
            ->where('seen', '=', '0')
            ->count();

        return view('map', ['unseenCount' => $unseenCount]);
    }


    public function mapStore(Request $request)
    {
        $selectedBusiness = urldecode($request->query('business'));

        // Fetch unseen message count
        $unseenCount = DB::table('ch_messages')
            ->where('to_id', '=', Auth::user()->id)
            ->where('seen', '=', '0')
            ->count();

        // Retrieve posts with store hours from the database
        $posts = Posts::select([
            'id', 'businessName', 'description', 'images', 'latitude', 'longitude', 'is_active',
            'monday_open', 'monday_close',
            'tuesday_open', 'tuesday_close',
            'wednesday_open', 'wednesday_close',
            'thursday_open', 'thursday_close',
            'friday_open', 'friday_close',
            'saturday_open', 'saturday_close',
            'sunday_open', 'sunday_close'
        ])->get();

        // Format store hours for each post
        $posts = $posts->map(function ($post) {
            $post->store_hours = $this->formatStoreHours($post);
            return $post;
        });

        // Get the business name from the query parameter
        $selectedBusiness = $request->query('business');

        return view('mapStore', [
            'posts' => $posts,
            'unseenCount' => $unseenCount,
            'selectedBusiness' => $selectedBusiness,
            'postsJson' => json_encode($posts), // Add this line
        ]);
    }

    private function formatStoreHours($post)
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];

        foreach ($days as $day) {
            $open = $post->{$day . '_open'};
            $close = $post->{$day . '_close'};

            if ($open && $close) {
                $hours[$day] = $this->formatTime($open) . ' - ' . $this->formatTime($close);
            } else {
                $hours[$day] = 'Closed';
            }
        }

        return $hours;
    }

    private function formatTime($time)
    {
        return date('g:i A', strtotime($time));
    }
}
