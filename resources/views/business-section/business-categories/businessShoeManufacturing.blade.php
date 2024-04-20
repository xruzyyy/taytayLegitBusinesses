@extends('layouts.app')

@section('content')

<!-- HTML -->
<!-- HTML -->
<section class="business-section-post" id="section3">
    <div class="container section3-container">
        <h2 class="animate-on-scroll">Latest Business Posts</h2>
          <!-- Search Form -->
        <form action="{{ route('showShoeManufacturingCategories') }}" method="GET">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Search Business Posts" name="search">
                <div class="input-group-append">
                    <button class="btn" type="submit" style="margin-top: 2.4em">Search</button>
                </div>
            </div>
        </form>
        <!-- Card Deck for Latest Business Posts -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 justify-content-between">
            @foreach ($categories as $post)
                <div class="col mb-4">
                    <div class="card h-100 ">
                        <!-- Only the image is wrapped in the onclick event -->
                        <img src="{{ asset($post->image) }}" class="card-img-top" alt="Business Image" onclick="openFullScreen('{{ route('businessPost', ['id' => $post->id]) }}')">
                        <div class="card-body">
                            <p class="card-text"><strong>Type:</strong> {{ $post->type }}</p>
                            <h5 class="card-title">{{ $post->businessName }}</h5>
                            <p class="card-text">{{ $post->description }}</p>
                            <!-- Display the type -->
                            <p class="card-text"><strong>Contact Number:</strong> {{ $post->contactNumber }}</p>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt" style="color: #006ce7f1;"></i>
                                <a href="{{ route('mapStore') }}" class="store-map-link" style="text-decoration: none;">
                                    <b style="color: black;">Explore Store on Map</b>
                                </a>
                            </p>
                            <!-- Updated HTML for the link -->
                            <a href="/chatify/{{ $post->user_id }}" class="message-link">
                                <b style="color:black;">Message:</b>
                                <i class="fa-brands fa-facebook-messenger"></i>
                                {{ $post->businessName }}
                            </a>
                            <!-- Add any other relevant information here -->
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
       <!-- Pagination Links -->
       <div class="d-flex justify-content-center mt-4">
        {{ $categories->links('pagination::bootstrap-4')   }}
    </div>
</section>
<!-- JavaScript -->
<script>
    // Function to open the business post in a new full-screen window
    function openFullScreen(url) {
        // Open a new window with the provided URL and make it full-screen
        window.open(url, '_blank', 'fullscreen=yes');
    }
</script>

@endsection
