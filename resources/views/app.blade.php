<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>MojaZastávka.sk - nájdite zastávku do Vášho cieľa</title>
	<meta name="author" content="Jozef Cipa">
	<meta name="keywords" content="">
	<meta name="description" content="">
	<meta name="viewport" content="initial-scale = 1.0,maximum-scale = 1.0" />
	<link rel="stylesheet" href="/dist/css/app.css">
</head>
<body>
	<main id="app">
		<header>
			<h2>MojaZastávka.sk</h2>
		</header>
		<section id="search">
			<form>
				<div class="input">
					<input type="text" placeholder="Vaša poloha">
				</div>
				<div class="input">
					<input type="text" placeholder="Cieľ" autofocus="autofocus">
				</div>
			</form>
		</section>
		<section id="google-map">
			<div id="map">Ak toto vidíte, niečo je zle</div>
			
			<div class="loader">
				<div id="img"></div>
			</div>
		</section>
	</main>
<script>
      function initMap() {

        var presovHlavna = {lat: 48.9976752, lng: 21.238496};
        var spse = {lat: 48.990631, lng: 21.2452944};

        var map = new google.maps.Map(document.getElementById('map'), {
          center: presovHlavna,
          scrollwheel: false,
          zoom: 7
        });

        var directionsDisplay = new google.maps.DirectionsRenderer({
        	polylineOptions: {
		      strokeColor: "#f64747",
		      strokeWeight: "3"
		    },
          map: map
        });

        // Set destination, origin and travel mode.
        var request = {
          destination: presovHlavna,
          origin: spse,
          travelMode: 'DRIVING'
        };

        // Pass the directions request to the directions service.
        var directionsService = new google.maps.DirectionsService();
        directionsService.route(request, function(response, status) {
          if (status == 'OK') {
            // Display the route on the map.
            directionsDisplay.setDirections(response);
          }
        });
      }

</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDg7pIc2cnPbAbRMI32VbTpFh5jToav8MA&callback=initMap" async defer></script>

{{-- <script src="/dist/js/vendor.js"></script> --}}
{{-- <script src="/dist/js/bundle.js"></script> --}}
<!-- <script src=""></script> Google Analytics--> 
</body>
</html>