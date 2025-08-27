// Request user's location
function requestLocation() {
  if ("geolocation" in navigator) {
    navigator.geolocation.getCurrentPosition(
      // Success callback
      function (position) {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        // Store location in session via AJAX
        fetch("/api/location/save_location.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            lat: lat,
            lon: lon,
          }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // Reload page to show nearby products
              window.location.reload();
            }
          })
          .catch((error) => console.error("Error:", error));
      },
      // Error callback
      function (error) {
        console.error("Error getting location:", error);
      },
      // Options
      {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0,
      }
    );
  }
}

// Request location when page loads
document.addEventListener("DOMContentLoaded", function () {
  // Only request location if not already set
  if (!document.querySelector("#nearby")) {
    requestLocation();
  }
});
