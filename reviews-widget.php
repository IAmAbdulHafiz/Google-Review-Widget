<?php
/**
 * reviews-widget.php
 *
 * Fetches Google Reviews from the Places API and displays a reviews widget.
 * - Shows overall rating, stars, and total review count.
 * - Provides a "Review us on Google" button.
 * - Displays reviews in a grid slider for desktop and as an accordion on mobile.
 * - Includes a verified badge, clickable reviewer's name, and formatted review date.
 */

// =============================================================================
// 1. Basic Headers and Security
// =============================================================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// =============================================================================
// 2. Configuration and API Setup
// =============================================================================
include("config.php");

$apiKey  = getenv('GOOGLE_API_KEY');  // Or hardcode the API key if needed.
$placeId = getenv('GOOGLE_PLACE_ID');   // Or hardcode the Place ID if needed.
$fields  = 'name,rating,reviews,user_ratings_total';

// =============================================================================
// 3. Retrieve Reviews from Google Places API
// =============================================================================
$url = "https://maps.googleapis.com/maps/api/place/details/json"
     . "?place_id={$placeId}"
     . "&fields={$fields}"
     . "&key={$apiKey}";

$response = file_get_contents($url);
if (!$response) {
    die('<p>Could not fetch reviews. Please check server logs or API configuration.</p>');
}

$data = json_decode($response, true);
if (!isset($data['result'])) {
    die('<p>Invalid response from Google. Check your Place ID or API key.</p>');
}

$reviews = $data['result']['reviews'] ?? [];
$overallRating = $data['result']['rating'] ?? 0;
$totalReviews = $data['result']['user_ratings_total'] ?? 0;

// =============================================================================
// 4. Process and Sort Reviews
// =============================================================================
// Sort reviews in descending order (latest first)
usort($reviews, function($a, $b) {
    return ($b['time'] ?? 0) - ($a['time'] ?? 0);
});

// For desktop view: All reviews appear in one grid slide.
$desktopSlides = [$reviews];

// For mobile view: Each review is its own slide.
$mobileSlides = array_chunk($reviews, 1);

// =============================================================================
// 5. Helper Functions
// =============================================================================
/**
 * Generates HTML for star ratings.
 */
function generateStars($rating) {
    $rounded = round($rating);
    $starsHtml = '';
    for ($i = 1; $i <= 5; $i++) {
        $starsHtml .= ($i <= $rounded)
            ? '<i class="star filled">&#9733;</i>'
            : '<i class="star">&#9734;</i>';
    }
    return $starsHtml;
}

/**
 * Truncates the reviewer's name if it exceeds a set length.
 */
function truncateName($name, $maxLength = 11) {
    return (strlen($name) > $maxLength) ? substr($name, 0, $maxLength) . '...' : $name;
}

/**
 * Formats the review date to "Today" or "X days ago."
 */
function daysAgo($reviewTime) {
    $diffDays = floor((time() - $reviewTime) / 86400);
    return ($diffDays <= 0) ? "Today" : $diffDays . " days ago";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Google Reviews Widget</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Link to your external CSS file -->
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="reviews-section">
    <!-- Section Header -->
    <header class="reviews-header">
      <h2>What Our Customers Say</h2>
      <div class="google-summary">
        <div class="google-summary-info">
          <img src="assets/images/google-reviews-logo.png" alt="Google Reviews Logo">
          <span class="rating-score"><?= number_format($overallRating, 1) ?></span>
          <div class="stars-container"><?= generateStars($overallRating) ?></div>
          <span class="total-reviews">(<?= $totalReviews ?> reviews)</span>
        </div>
        <div class="google-summary-button">
          <a class="review-button" href="https://search.google.com/local/reviews?placeid=<?= $placeId ?>" target="_blank" rel="noopener noreferrer">
            Review us on Google
          </a>
        </div>
      </div>
    </header>

    <!-- Desktop Slider (Grid Layout) -->
    <div class="slider-container desktop-slider-container">
      <div class="slider" id="desktopSlider">
        <?php foreach ($desktopSlides as $slideReviews): ?>
          <div class="slide">
            <?php foreach ($slideReviews as $review):
              $authorName  = htmlspecialchars($review['author_name'] ?? 'Anonymous');
              $displayName = truncateName($authorName);
              // Use htmlspecialchars() on the URL to avoid problems with special characters.
              $photoUrl    = htmlspecialchars($review['profile_photo_url'] ?? 'https://via.placeholder.com/40');
              $rating      = $review['rating'] ?? 0;
              $text        = htmlspecialchars($review['text'] ?? '');
              $reviewTime  = $review['time'] ?? time();
              $reviewAge   = daysAgo($reviewTime);
              $reviewUrl   = "https://search.google.com/local/reviews?placeid={$placeId}";
            ?>
              <div class="review-card">
                <div class="review-author">
                  <img src="<?= $photoUrl ?>" alt="Reviewer">
                  <div>
                    <h3 class="reviewer-name">
                      <a href="<?= $reviewUrl ?>" target="_blank"><?= $displayName ?></a>
                      <a href="<?= $reviewUrl ?>" target="_blank">
                        <img src="assets/images/verified-badge.png" alt="Verified Badge" class="verified-badge" title="Verified reviewer on Google" style="width: 16px; height: 16px;">
                      </a>
                    </h3>
                    <span class="review-time"><?= $reviewAge ?></span>
                  </div>
                </div>
                <div class="review-rating"><?= generateStars($rating) ?></div>
                <p class="review-text"><?= $text ?></p>
                <a class="read-more" href="<?= $reviewUrl ?>" target="_blank">Read more</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Mobile Accordion Layout -->
    <div class="accordion-container mobile-accordion-container">
      <?php foreach ($mobileSlides as $slide): 
        $review      = $slide[0];
        $authorName  = htmlspecialchars($review['author_name'] ?? 'Anonymous');
        $photoUrl    = htmlspecialchars($review['profile_photo_url'] ?? 'https://via.placeholder.com/40');
        $rating      = $review['rating'] ?? 0;
        $text        = htmlspecialchars($review['text'] ?? '');
        $reviewTime  = $review['time'] ?? time();
        $reviewAge   = daysAgo($reviewTime);
        $reviewUrl   = "https://search.google.com/local/reviews?placeid={$placeId}";
      ?>
        <div class="accordion-item">
          <button class="accordion-toggle">
            <img src="<?= $photoUrl ?>" alt="Reviewer">
            <span>
              <?= $authorName ?> 
              <a href="<?= $reviewUrl ?>" target="_blank">
                <img src="assets/images/verified-badge.png" alt="Verified Badge" class="verified-badge" title="Verified reviewer on Google" style="width: 16px; height: 16px;">
              </a>
               <span style="color: #FFA500;"><?= generateStars($rating) ?></span>
            </span>
          </button>
          <div class="accordion-content">
            <p class="review-text"><?= $text ?></p>
            <div class="review-meta">
              <span><?= $reviewAge ?></span> | <a href="<?= $reviewUrl ?>" target="_blank">Read more on Google</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="view-more-reviews">
        <a class="review-button" href="https://search.google.com/local/reviews?placeid=<?= $placeId ?>" target="_blank" rel="noopener noreferrer">
            View More Reviews
        </a>
      </div>
    </div>
  </div>

  <!-- Simple Accordion Toggle Script -->
  <script>
    document.querySelectorAll('.accordion-toggle').forEach((toggle) => {
      toggle.addEventListener('click', () => {
        const content = toggle.nextElementSibling;
        content.classList.toggle('active');
      });
    });
  </script>
</body>
</html>