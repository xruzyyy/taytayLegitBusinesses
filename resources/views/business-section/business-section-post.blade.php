<style>
    a{
        text-decoration: none;
    }
</style>
<section class="business-section-post" id="section3">
    <div class="container section3-container">
        <h2 class="animate-on-scroll" style="color: rgb(3, 0, 0);">
            Latest Business Posts
        </h2>

        <!-- Card Deck for Latest Business Posts -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 justify-content-between">
            @foreach ($posts as $post)
                <div class="col mb-4">
                    <div class="card h-100 animate-on-scroll">
                        <!-- Display only the first image from the array -->
                        @php
                            $images = json_decode($post->images);
                            $firstImage = isset($images[0]) ? $images[0] : null;
                            $current_time = \Carbon\Carbon::now()->setTimezone(config('app.timezone'));
                            $current_day = strtolower($current_time->format('l')); // Get the current day of the week in lowercase
                            $open_field = $current_day . '_open';
                            $close_field = $current_day . '_close';
                            $open_time = \Carbon\Carbon::parse($post->$open_field)->setTimezone(config('app.timezone'));
                            $close_time = \Carbon\Carbon::parse($post->$close_field)->setTimezone(config('app.timezone'));
                            $is_open = $current_time->between($open_time, $close_time);
                        @endphp
                        <img src="{{ asset($firstImage) }}" class="card-img-top" alt="Business Image"
                            onclick="openFullScreen('{{ route('businessPost', ['id' => $post->id]) }}')">
                        <div class="card-body">
                            <h5>{{ \Illuminate\Support\Str::limit($post->businessName, 12) }}</h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt mr-2" style="color: #006ce7f1;"></i>
                                <a href="{{ route('mapStore', ['business' => rawurlencode($post->businessName)]) }}" class="store-map-link">
                                    View Map
                                </a>
                                <a href="/chatify/{{ $post->user_id }}" class="message-link ml-3">
                                    <i class="fa-brands fa-facebook-messenger mr-1" style="color: #006ce7f1;"></i>
                                    Message
                                </a>
                            </p>
                            <div style="margin: 0; font-weight:bolder;"><strong>Type:</strong> {{ $post->type }}</div>

                            <!-- Display the is_active status -->
                            <div style="margin: 0" class="card-text">
                                <strong style="font-weight:500; color:darkgoldenrod;">Permit Status:</strong>
                                @if ($post->is_active)
                                <span style="color: green"><b>Active</b></span>
                                @else
                                    <span style="color: red"><b>Permit Not Active</b></span>
                                @endif
                            </div>

                            @if ($post->$open_field && $post->$close_field)
                                <div style="margin: 0">
                                    <strong>{{ ucfirst($current_day) }}:</strong>
                                    {{ $open_time->format('h:i A') }} - {{ $close_time->format('h:i A') }}
                                    @if ($is_open)
                                        <span style="color: green"><b>Open</b></span>
                                    @else
                                        <span style="color: red"><b>Closed</b></span>
                                    @endif
                                </div>
                            @endif

                            <strong>Ratings: <span id="average-rating-{{ $post->id }}">{{ number_format($post->ratings()->avg('rating'), 2) ?? 'Not Rated' }}</span>
                                (<span id="ratings-count-{{ $post->id }}">{{ $post->ratings()->count() }}</span> reviews)
                            </strong>
                            <strong>Comments:</strong> <span id="comments-count-{{ $post->id }}">{{ $post->comments()->count() }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- JavaScript -->
<script>
    // Function to open the business post in a new full-screen window
    function openFullScreen(url) {
        // Open a new window with the provided URL and make it full-screen
        window.open(url, '_blank', 'fullscreen=yes');
    }

    // Wait for the DOM content to be fully loaded
    document.addEventListener("DOMContentLoaded", function() {
        // Get elements containing raw numbers and format them
        @foreach ($posts as $post)
            formatNumbers('{{ $post->id }}');
        @endforeach
    });

    // Function to format numbers with appropriate suffixes and style "k" with green color
    function formatNumbers(postId) {
        var averageRatingElement = document.getElementById("average-rating-" + postId);
        var ratingsCountElement = document.getElementById("ratings-count-" + postId);
        var commentsCountElement = document.getElementById("comments-count-" + postId);

        if (averageRatingElement) {
            averageRatingElement.innerHTML = formatNumber(averageRatingElement.innerHTML);
        }
        if (ratingsCountElement) {
            ratingsCountElement.innerHTML = formatNumber(ratingsCountElement.innerHTML);
        }
        if (commentsCountElement) {
            commentsCountElement.innerHTML = formatNumber(commentsCountElement.innerHTML);
        }
    }

    // Function to format numbers with appropriate suffixes
    function formatNumber(number) {
        if (number >= 1000 && number < 1000000) {
            return (number / 1000).toFixed(1) + "<span class='number-suffix'>k</span>";
        } else if (number >= 1000000) {
            return (number / 1000000).toFixed(1) + "<span class='number-suffix'>M</span>";
        }
        return number;
    }
</script>


<style>
    p {
    font-size: 16px !important;
    font-weight: 500 !important;
    color: #c3cad9
}
</style>
<!-- JavaScript -->
<script>
    // Function to open the business post in a new full-screen window
    function openFullScreen(url) {
        // Open a new window with the provided URL and make it full-screen
        window.open(url, '_blank', 'fullscreen=yes');
    }

    // Wait for the DOM content to be fully loaded
    document.addEventListener("DOMContentLoaded", function() {
        // Get elements containing raw numbers and format them
        @foreach ($posts as $post)
            formatNumbers('{{ $post->id }}');
        @endforeach
    });

    // Function to format numbers with appropriate suffixes and style "k" with green color
    function formatNumbers(postId) {
        var averageRatingElement = document.getElementById("average-rating-" + postId);
        var ratingsCountElement = document.getElementById("ratings-count-" + postId);
        var commentsCountElement = document.getElementById("comments-count-" + postId);

        if (averageRatingElement) {
            averageRatingElement.innerHTML = formatNumber(averageRatingElement.innerHTML);
        }
        if (ratingsCountElement) {
            ratingsCountElement.innerHTML = formatNumber(ratingsCountElement.innerHTML);
        }
        if (commentsCountElement) {
            commentsCountElement.innerHTML = formatNumber(commentsCountElement.innerHTML);
        }
    }

    // Function to format numbers with appropriate suffixes
    function formatNumber(number) {
        if (number >= 1000 && number < 1000000) {
            return (number / 1000).toFixed(1) + "<span class='number-suffix'>k</span>";
        } else if (number >= 1000000) {
            return (number / 1000000).toFixed(1) + "<span class='number-suffix'>M</span>";
        }
        return number;
    }
</script>
