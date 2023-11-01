<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPhotos</title>
    <meta name="generator" content="PHPhotos - https://github.com/svandragt/phphotos" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <style>
        body {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Fluid columns */
            gap: 20px; /* Adjust the gap between grid items as needed */
            justify-items: center; /* Horizontally center items within each grid cell */
            font-family: 'Poppins', sans-serif;
        }

        figure {
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
            cursor: pointer;
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
            max-width: 90%;
            max-height: 90%;
            margin: 60px auto 0;
        }

        .modal figcaption {
            color: white;
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
	$imageHash = hash( 'md5', $image . filemtime( $image ) );
	$thumbnailPath = $cacheDir . $imageHash . '.jpg';

	// Create a 16:9 crop and resize to 300px wide
	if ( ! file_exists( $thumbnailPath ) ) {
		createThumbnail( $image, $thumbnailPath, 300, 169 );
	}

	// Output HTML figure tags
	echo '<figure data-image="' . $image . '" tabindex="0" role="button">';
	echo '<img src="' . $thumbnailPath . '" alt="Thumbnail">';
	echo '<figcaption>' . getSanitizedCaption( $image ) . '</figcaption>';
	echo '</figure>';
}

function getSanitizedCaption( string $filename ) : string {
	$filenameWithoutExtension = pathinfo( $filename, PATHINFO_FILENAME );

	return ucfirst( str_replace( "_", " ", $filenameWithoutExtension ) );
}

// Function to create a 16:9 cropped thumbnail
function createThumbnail( $source, $destination, $width, $height ) : void {
	[ $srcWidth, $srcHeight, $imageType ] = getimagesize( $source );

	if ( $imageType === IMAGETYPE_PNG ) {
		$sourceImage = imagecreatefrompng( $source );
	} elseif ( $imageType === IMAGETYPE_JPEG ) {
		$sourceImage = imagecreatefromjpeg( $source );
	} else {
		// Unsupported image type
		return;
	}

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

	imagecopyresampled( $thumb, $sourceImage, 0, 0, $cropX, $cropY, $width, $height, $newWidth, $newHeight );

	if ( $imageType === IMAGETYPE_PNG ) {
		imagejpeg( $thumb, $destination, 90 );
	} elseif ( $imageType === IMAGETYPE_JPEG ) {
		imagejpeg( $thumb, $destination, 90 );
	}

	imagedestroy( $thumb );
	imagedestroy( $sourceImage );
}

?>
<div id="imageModal" class="modal">
    <span role="button" class="close" id="closeModal" tabindex="0">&times;</span>

    <figure>
        <img id="modalImage" src="" alt="Modal Image">
        <figcaption id="modalCaption"></figcaption>
    </figure>
</div>

<script>
    // Get the modal, close button, and modal image
    const modal = document.getElementById('imageModal');
    const closeModal = document.getElementById('closeModal');
    const modalImage = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');

    // Get all figure elements, excluding modal
    const figures = document.querySelectorAll('body > figure');

    // Variable to track the currently displayed image index
    let currentImageIndex = 0;

    // Function to open the modal
    function openModal(event, index) {
        currentImageIndex = index;
        modalImage.src = event.currentTarget.getAttribute('data-image');
        modalCaption.textContent = event.currentTarget.getElementsByTagName("figcaption")[0].textContent;
        modal.style.display = 'block';
    }

    // Function to close the modal
    function closeModalFunc() {
        modal.style.display = 'none';
    }

    // Function to navigate to the next image
    function nextImage() {
        // modal itself is also one
        if (currentImageIndex < figures.length - 1) {
            currentImageIndex++;
            openModal({currentTarget: figures[currentImageIndex]}, currentImageIndex);
        }
    }

    // Function to navigate to the previous image
    function previousImage() {
        if (currentImageIndex > 0) {
            currentImageIndex--;
            openModal({currentTarget: figures[currentImageIndex]}, currentImageIndex);
        }
    }

    // Attach click event listeners to figures
    figures.forEach(function (figure, index) {
        figure.addEventListener('click', function (event) {
            openModal(event, index);
        });
        figure.addEventListener('keydown', function (event) {
            if (event.key === "Enter" || event.key === " ") {
                openModal(event, index);
            }
        });
    });

    // Attach click event listener to close button
    closeModal.addEventListener('click', closeModalFunc);
    closeModal.addEventListener('keydown', function (event) {
        // Check for Enter key (key code 13) or Space key (key code 32)
        if (event.key === "Enter" || event.key === " ") {
            closeModalFunc();
        }
    });
    modal.addEventListener('click', closeModalFunc);
    modal.addEventListener('keydown', function (event) {
        // Check for Enter key (key code 13) or Space key (key code 32)
        if (event.key === "Enter" || event.key === " ") {
            closeModalFunc();
        }
    });

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
