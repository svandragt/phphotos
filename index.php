<?php
$requestedFile = __DIR__ . $_SERVER['REQUEST_URI'];

// Check if the requested file exists and is an image (e.g., .jpg, .png, .gif)
if ( file_exists( $requestedFile ) && preg_match( "/\.(jpg|jpeg|png|gif)$/i", $requestedFile ) ) {
	$fileInfo = pathinfo( $requestedFile );
	$fileExtension = $fileInfo['extension'];
	$contentType = 'image/' . $fileExtension;

	// Set the appropriate content type for the image
	header( 'Content-Type: ' . $contentType );

	// Output the image file directly
	readfile( $requestedFile );
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Thumbnails</title>
    <meta name="description" content="Image Thumbnails with HTML figure tags">
    <meta name="author" content="Sander van Dragt">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <style>
        body {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Fluid columns */
            gap: 20px; /* Adjust the gap between grid items as needed */
            justify-items: center; /* Horizontally center items within each grid cell */
            font-family: 'Poppins', sans-serif;
        }

        .figure {
            display: flex;
            flex-direction: column;
            align-items: center; /* Vertically center items within the figure */
            text-align: center;
            padding: 1px;
        }

        /* Adjust these styles for your specific needs */
        img {
            max-width: 100%; /* Ensure images don't exceed their container width */
            height: auto; /* Maintain image aspect ratio */
            border-radius: 8px;
        }

    </style>
    <style>
        .modal {
  display: none;
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.9);
}

.modal img {
  display: block;
  margin: 0 auto;
  max-width: 90%;
  max-height: 90%;
  margin-top: 10%;
}

.close {
  position: absolute;
  top: 15px;
  right: 35px;
  font-size: 30px;
  cursor: pointer;
  color: white;
}

    </style>
</head>
<body>
<?php
// Define the source and cache directories
$sourceDir = 'photos/';
$cacheDir = 'cache/';

// Get a list of image files in the source directory
$images = glob( $sourceDir . '*.{jpg,png,gif}', GLOB_BRACE );

// Loop through each image and create thumbnails
foreach ( $images as $image ) {
	$imageHash = hash_file( 'md5', $image );
	$thumbnailPath = $cacheDir . $imageHash . '.jpg';

	// Create a 16:9 crop and resize to 300px wide
    if (!file_exists( $thumbnailPath)) {
    	createThumbnail( $image, $thumbnailPath, 300, 169 );
    }

	// Output HTML figure tags
    echo '<figure data-image="' . $image . '">';
	echo '<img src="' . $thumbnailPath . '" alt="Thumbnail">';
	echo '<figcaption>' . getSanitizedCaption( $image ) . '</figcaption>';
	echo '</figure>';
}

function getSanitizedCaption( $filename ) {
	$filenameWithoutExtension = pathinfo( $filename, PATHINFO_FILENAME );
	$sanitizedCaption = ucfirst( str_replace( "_", " ", $filenameWithoutExtension ) );
	$words = explode( ' ', $sanitizedCaption );

	// Remove the first two words and the last word (the ID)
	array_pop( $words ); // Remove the last word
	array_shift( $words ); // Remove the first word
	array_shift( $words ); // Remove the second word

	$sanitizedCaption = implode( ' ', $words );

	// Reconstruct the sanitized caption
	return $sanitizedCaption;
}

// Function to create a 16:9 cropped thumbnail
function createThumbnail( $source, $destination, $width, $height ) {
	list( $srcWidth, $srcHeight ) = getimagesize( $source );
	$srcAspect = $srcWidth / $srcHeight;
	$cropX = 0;
	$cropY = 0;

	$newWidth = $srcWidth;
	$newHeight = $srcHeight;

	if ( $srcAspect > 16 / 9 ) {
		// Source image is wider, crop the sides
		$newWidth = $srcHeight * 16 / 9;
		$cropX = ( $srcWidth - $newWidth ) / 2;
	} else {
		// Source image is taller, crop the top and bottom
		$newHeight = $srcWidth * 9 / 16;
		$cropY = ( $srcHeight - $newHeight ) / 2;
	}

	$thumb = imagecreatetruecolor( $width, $height );
	$sourceImage = imagecreatefrompng( $source );

	imagecopyresampled( $thumb, $sourceImage, 0, 0, $cropX, $cropY, $width, $height, $newWidth, $newHeight );

	imagejpeg( $thumb, $destination, 90 );

	imagedestroy( $thumb );
	imagedestroy( $sourceImage );
}

?>
<div id="imageModal" class="modal">
  <span class="close" id="closeModal">&times;</span>
  <img id="modalImage" src="" alt="Modal Image">
</div>

<script>
// Get the modal, close button, and modal image
var modal = document.getElementById('imageModal');
var closeModal = document.getElementById('closeModal');
var modalImage = document.getElementById('modalImage');

// Get all figure elements
var figures = document.querySelectorAll('figure');

// Variable to track the currently displayed image index
var currentImageIndex = 0;

// Function to open the modal
function openModal(event, index) {
    currentImageIndex = index;
    var image = event.currentTarget.getAttribute('data-image');
    modalImage.src = image;
    modal.style.display = 'block';
}

// Function to close the modal
function closeModalFunc() {
    modal.style.display = 'none';
}

// Function to navigate to the next image
function nextImage() {
    if (currentImageIndex < figures.length - 1) {
        currentImageIndex++;
        openModal({ currentTarget: figures[currentImageIndex] }, currentImageIndex);
    }
}

// Function to navigate to the previous image
function previousImage() {
    if (currentImageIndex > 0) {
        currentImageIndex--;
        openModal({ currentTarget: figures[currentImageIndex] }, currentImageIndex);
    }
}

// Attach click event listeners to figures
figures.forEach(function (figure, index) {
    figure.addEventListener('click', function (event) {
        openModal(event, index);
    });
});

// Attach click event listener to close button
closeModal.addEventListener('click', closeModalFunc);

// Attach keyboard event listeners
document.addEventListener('keydown', function (event) {
    if (modal.style.display === 'block') {
        switch (event.key) {
            case 'ArrowRight':
                nextImage();
                break;
            case 'ArrowLeft':
                previousImage();
                break;
            case 'Escape':
                closeModalFunc();
                break;
        }
    }
});


</script>
</body>
</html>
