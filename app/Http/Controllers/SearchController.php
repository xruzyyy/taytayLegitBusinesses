<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Posts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function searchCategories(Request $request)
    {
        // Retrieve search query from the request
        $searchQuery = $request->input('search');

        // Retrieve unseen message count
        $unseenCount = DB::table('ch_messages')
            ->where('to_id', '=', Auth::user()->id)
            ->where('seen', '=', '0')
            ->count();

        // Query all categories
        $postsQuery = Posts::query();

        // Apply search if search query is provided
        if ($searchQuery) {
            $postsQuery->where(function ($query) use ($searchQuery) {
                $query->where('businessName', 'like', '%' . $searchQuery . '%')
                    ->orWhere('description', 'like', '%' . $searchQuery . '%');
            });
        }

        // Paginate the results with 10 businesses per page
        $posts = $postsQuery->paginate(10);

        // Pass the retrieved categories to the view for display
        return view('business-section.business-categories.searchResults', [
            'posts' => $posts,
            'unseenCount' => $unseenCount,
        ]);
    }
}
