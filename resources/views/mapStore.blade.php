<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaflet Map with Directions</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="map.css">
    <script src="map.js"></script>
    <style>
      body {
        font-family: "Arial", sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: #f4f4f4;
      }

      #map-container {
        display: flex;
        flex: 1;
        position: relative;
      }

      #map {
        flex: 1;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      #search-form {
        width: 300px;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: #fff;
        margin: 20px;
        display: flex;
        flex-direction: column;
      }

      h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #007bff;
        font-size: 1.5em;
      }

      label {
        display: block;
        font-weight: bold;
        margin-bottom: 10px;
        color: #333;
        font-size: 0.9em;
      }

      input {
        padding: 8px;
        margin-bottom: 15px;
        width: calc(100% - 18px);
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 0.9em;
      }

      button {
        padding: 10px 20px;
        cursor: pointer;
        border: none;
        border-radius: 4px;
        color: #fff;
        background-color: #007bff;
        transition: background-color 0.3s ease-in-out;
        font-size: 1em;
        margin-top: 10px;
      }

      button:hover {
        background-color: #0056b3;
      }

      /* Leaflet Routing Machine Container Styling */
      #directions-container {
        margin: 20px;
        width: 300px;
        max-width: 100%;
      }

      .leaflet-routing-container {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
      }
    </style>
  </head>

<body>
  <div id="map-container">
    <div id="map"></div>

    <div id="search-form">
      <h2>Find Directions</h2>
      <label for="start">From:</label>
      <input type="text" id="start" placeholder="Enter start location">

      <label for="end">To:</label>
      <input type="text" id="end" placeholder="Enter end location">

      <button onclick="useCurrentLocation('start')">Use Current Location</button>
      <!-- Updated the button id to "search-button" -->
      <button id="search-button" onclick="getDirections()">Get Directions</button>
    </div>

    <!-- Leaflet Routing Machine Container -->
    <div id="directions-container">
      <div class="leaflet-routing-container leaflet-bar leaflet-control">
        <!-- Routing controls and directions information will be placed here -->
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
  <script>
    // Assuming you have already fetched the categories data from your database in PHP
var categories = {!! json_encode($categories) !!};


var map = L.map("map").setView([14.5695, 121.1126], 13);

L.tileLayer("https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}", {
  maxZoom: 20,
  subdomains: ["mt0", "mt1", "mt2", "mt3"],
  attribution: "&copy; Google Maps"
}).addTo(map);

function addCategoryMarkers() {
    var categories = {!! json_encode($categories) !!};

categories.forEach(function(category) {
    var marker = L.marker([category.latitude, category.longitude]).addTo(map);
    marker.options.businessName = category.businessName; // Store businessName as a property of the marker
    marker.options.description = category.description; // Store description as a property of the marker
    marker.options.image = category.image; // Store image as a property of the marker
    marker.bindPopup("<b>" + category.businessName + "</b><br>" + category.description + "<br><img src='" + category.image + "' width='100'>");
    
});
}

addCategoryMarkers();

function getUserLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function(position) {
        var userLatLng = L.latLng(
          position.coords.latitude,
          position.coords.longitude
        );
        map.setView(userLatLng, 13);
        updateUserLocationMarker(userLatLng);
        document.getElementById("start").value = "Current Location";
      },
      function(error) {
        console.error("Error getting user location:", error.message);
      },
      { maximumAge: 60000, timeout: 5000, enableHighAccuracy: true }
    );
  } else {
    console.error("Geolocation is not supported by this browser.");
  }
}

getUserLocation();

var userLocationMarker;

function updateUserLocationMarker(userLatLng) {
  if (!userLocationMarker) {
    userLocationMarker = L.marker(userLatLng).addTo(map);
    userLocationMarker.bindPopup("Your Location").openPopup();
    userLocationMarker.on("click", function() {
      showStreetView(userLocationMarker.getLatLng());
    });
  } else {
    userLocationMarker.setLatLng(userLatLng);
  }

  document.getElementById("latitude").innerText = userLatLng.lat.toFixed(6);
  document.getElementById("longitude").innerText = userLatLng.lng.toFixed(6);
}

function useCurrentLocation(field) {
  getUserLocation();
  document.getElementById(field).value = "Current Location";
}

var routingControl = L.Routing.control({
  routeWhileDragging: true,
  waypoints: [],
  createMarker: function(i, waypoint, n) {
    if (i === 0) {
      var marker = L.marker(waypoint.latLng);
      map.addLayer(marker);
      return marker;
    }
    return null;
  }
});

function appendRoutingControl() {
  var directionsContainer = document.getElementById("directions-container");
  directionsContainer.appendChild(routingControl.onAdd(map));
}

appendRoutingControl();

function getDirections() {
    var startName = document.getElementById("start").value;
    var endName = document.getElementById("end").value;
    var waypoints = [];

    if (startName.toLowerCase().includes("current")) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var userLatLng = [position.coords.latitude, position.coords.longitude];
                waypoints.push(L.latLng(userLatLng[0], userLatLng[1]));
                // Retrieve the end location based on the selected suggestion
                var endLocation = findLocationByName(endName);
                if (endLocation !== null) {
                    waypoints.push(L.latLng(endLocation.lat, endLocation.lng));
                    clearMarkers();
                    routingControl.setWaypoints(waypoints);
                } else {
                    alert("Unable to find end location.");
                }
            },
            function(error) {
                console.error("Error getting user location:", error.message);
            }
        );
    } else {
        var startLocation = findLocationByName(startName);
        if (startLocation !== null) {
            waypoints.push(L.latLng(startLocation.lat, startLocation.lng));
            // Retrieve the end location based on the selected suggestion
            var endLocation = findLocationByName(endName);
            if (endLocation !== null) {
                waypoints.push(L.latLng(endLocation.lat, endLocation.lng));
                clearMarkers();
                routingControl.setWaypoints(waypoints);
            } else {
                alert("Unable to find end location.");
            }
        } else {
            alert("Unable to find start location.");
        }
    }
}

function findLocationByName(name) {
    // Search for the location by business name in the categories array
    for (var i = 0; i < categories.length; i++) {
        if (categories[i].businessName.toLowerCase() === name.toLowerCase()) {
            return {
                lat: categories[i].latitude,
                lng: categories[i].longitude
            };
        }
    }
    return null; // Return null if the location is not found
}


function clearMarkers() {
  routingControl.getPlan().setWaypoints([]);
}

function findMarkerByBusinessName(name) {
  for (var i = 0; i < map._layers.length; i++) {
    var layer = map._layers[i];
    if (layer instanceof L.Marker && layer.businessName === name) {
      return layer;
    }
  }
  return null;
}

var startInput = document.getElementById("start");
var endInput = document.getElementById("end");

startInput.addEventListener("input", function() {
  var inputValue = this.value.toLowerCase();
  var suggestions = categories.filter(function(category) {
    return category.businessName.toLowerCase().includes(inputValue);
  });

  var suggestedNames = suggestions.map(function(category) {
    return category.businessName;
  });

  showSuggestions(startInput, suggestedNames);
});

endInput.addEventListener("input", function() {
  var inputValue = this.value.toLowerCase();
  var suggestions = categories.filter(function(category) {
    return category.businessName.toLowerCase().includes(inputValue);
  });

  var suggestedNames = suggestions.map(function(category) {
    return category.businessName;
  });

  showSuggestions(endInput, suggestedNames);
});

function showSuggestions(inputField, suggestions) {
  var autocompleteList = document.createElement("div");
  autocompleteList.setAttribute("class", "autocomplete-items");
  removePreviousSuggestions();

  for (var i = 0; i < suggestions.length; i++) {
    var suggestion = document.createElement("div");
    suggestion.innerHTML =
      "<strong>" +
      suggestions[i].substr(0, inputField.value.length) +
      "</strong>";
    suggestion.innerHTML += suggestions[i].substr(inputField.value.length);
    suggestion.innerHTML +=
      "<input type='hidden' value='" + suggestions[i] + "'>";
    suggestion.addEventListener("click", function() {
      inputField.value = this.getElementsByTagName("input")[0].value;
      removePreviousSuggestions();
    });
    autocompleteList.appendChild(suggestion);
  }

  inputField.parentNode.appendChild(autocompleteList);

  document.addEventListener("click", function(event) {
    if (event.target !== inputField) {
      removePreviousSuggestions();
    }
  });
}

function removePreviousSuggestions() {
  var autocompleteItems = document.querySelectorAll(".autocomplete-items");
  for (var i = 0; i < autocompleteItems.length; i++) {
    autocompleteItems[i].parentNode.removeChild(autocompleteItems[i]);
  }
}


  </script>
</body>

</html>
