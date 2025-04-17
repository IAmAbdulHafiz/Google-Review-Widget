<?php
/**
 * reviews-widget.php
 *
 * A complete PHP script that:
 * 1. Fetches Google Reviews from the Places API.
 * 2. Displays the overall rating, stars, total number of reviews, and a "Review us on Google" button.
 * 3. Splits the (up to 5) reviews into a slider with left/right navigation arrows.
 * 4. Attaches a verified badge (using an image) next to each reviewer's name.
 * 5. Makes the reviewer's name clickable, linking to their review, and adds a "Google" link on the next line.
 * 6. Truncates reviewer names to 11 characters with an ellipsis if they exceed that length.
 * 7. Displays the review date as "X days ago" (or "Today" if less than one day).
 */

// -------------------------------
// 1) BASIC HEADERS & SECURITY
// -------------------------------
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// -------------------------------
// 2) CONFIGURATION
// -------------------------------
// Load environment variables (if you use config.php)
include("config.php");

$apiKey  = getenv('GOOGLE_API_KEY'); // or directly: 'YOUR_API_KEY'
$placeId = getenv('GOOGLE_PLACE_ID');  // or directly: 'YOUR_PLACE_ID'
$fields  = 'name,rating,reviews,user_ratings_total';  // Fields to fetch

// -------------------------------
// 3) FETCH REVIEWS FROM GOOGLE
// -------------------------------
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

// Get reviews, overall rating, and total number of ratings (if available)
$reviews = $data['result']['reviews'] ?? [];
$overallRating = $data['result']['rating'] ?? 0;
$totalReviews = $data['result']['user_ratings_total'] ?? 0;

// Sort reviews by time in descending order (latest first)
usort($reviews, function($a, $b) {
    return ($b['time'] ?? 0) - ($a['time'] ?? 0);
});

// For the slider, we are working with the reviews provided (typically 5)
$reviewsPerSlide = count($reviews); // API returns a maximum of 5 reviews

// Group reviews into a single slide (API returns max 5 reviews)
$slides = [$reviews];
$totalSlides = count($slides);

// -------------------------------
// 4) HELPER FUNCTIONS
// -------------------------------

/**
 * Generates star icons based on the rating.
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
 * Truncates the reviewer name if it exceeds the maximum length.
 *
 * @param string $name
 * @param int $maxLength
 * @return string
 */
function truncateName($name, $maxLength = 11) {
    if (strlen($name) > $maxLength) {
        return substr($name, 0, $maxLength) . '...';
    }
    return $name;
}

/**
 * Returns a formatted string showing how many days ago the review was posted.
 *
 * @param int $reviewTime UNIX timestamp of the review.
 * @return string
 */
function daysAgo($reviewTime) {
    $diffDays = floor((time() - $reviewTime) / 86400);
    return ($diffDays <= 0) ? "Today" : $diffDays . " days ago";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Google Reviews Widget</title>
  <style>
    /* General Container & Header */
    .reviews-section {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px 20px;
      font-family: Arial, sans-serif;
      color: #333;
      position: relative;
    }
    .reviews-header {
      text-align: center;
      margin-bottom: 20px;
    }
    .reviews-header h2 {
      font-size: 1.8rem;
      margin-bottom: 10px;
    }
    .google-summary {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 3rem;
      gap: 8px;
      flex-wrap: wrap;
      background-color:rgb(248, 241, 227);
      border-radius: 8px;
      padding: 1.5rem 0.7rem;
      box-shadow: 0 2px 4px rgba(2, 2, 2, 0.2);
    }
    .google-summary img {
      width: 150px;
      height: auto;
      padding: 0 10px;
    }
    .rating-score {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .stars-container .star {
      color: #FFA500;
      margin-right: 2px;
      font-size: 1.2rem;
    }
    .total-reviews {
      font-size: 1rem;
      color: #555;
      margin-left: 8px;
    }
    .google-summary-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }
    .google-summary-button {
      flex-grow: 1;
      text-align: right;
      padding: 0 20px;
    }
    .reviews-header a.review-button {
      background-color: #4285F4;
      color: #fff;
      text-decoration: none;
      padding: 12px 12px;
      border-radius: 4px;
      font-size: 0.9rem;
      margin-top: 10px;
      display: inline-block;
    }
    .reviews-header a.review-button:hover {
      background-color: #ec983e;
    }

    /* Slider Styles */
    .slider-container {
      position: relative;
      overflow: hidden;
      margin-top: 30px;
    }
    .slider {
      display: flex;
      transition: transform 0.5s ease-in-out;
      width: calc(100% * <?php echo $totalSlides; ?>);
    }
    .slide {
      width: calc(100% / <?php echo $totalSlides; ?>);
      display: grid;
      grid-template-columns: repeat(<?php echo $reviewsPerSlide; ?>, 1fr);
      gap: 20px;
      padding: 0 10px;
      box-sizing: border-box;
    }

    /* Review Card Styles */
    .review-card {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 20px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background-color:rgb(248, 241, 227);
    }
    .review-author {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }
    .review-author img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 10px;
    }
    .review-author h3 {
      margin: 0;
      font-size: 0.8rem;
      font-weight: bold;
    }
    /* Verified badge using image */
    .verified-badge {
      width: 16px;
      height: 16px;
      margin-left: 5px;
      vertical-align: middle;
    }
    .review-time {
      font-size: 0.85rem;
      color: #777;
      margin-top: 2px;
      display: block;
    }
    /* "Google" link below the date moved to next line */
    .review-google-link {
      font-size: 0.8rem;
      color: #4285F4;
      text-decoration: none;
      margin-top: 4px;
      display: block;
    }
    .review-rating {
      margin-bottom: 10px;
    }
    .review-rating .star {
      color: #FFA500;
      margin-right: 2px;
      font-size: 1.1rem;
    }
    .review-text {
      font-size: 0.95rem;
      line-height: 1.4;
      color: #444;
      max-height: 60px;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 10px;
    }
    .read-more {
      color: #4285F4;
      text-decoration: none;
      font-size: 0.85rem;
      margin-top: auto;
      display: inline-block;
    }

    /* Navigation Arrows */
    .nav-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: rgba(66, 133, 244, 0.8);
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      font-size: 1.2rem;
      cursor: pointer;
      z-index: 10;
      outline: none;
    }
    .nav-arrow:hover {
      background-color: rgba(66, 133, 244, 1);
    }
    .nav-left {
      left: 10px;
    }
    .nav-right {
      right: 10px;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
      .reviews-section {
        padding: 20px 10px;
      }
      .google-summary {
        flex-direction: column;
        text-align: center;
      }
      .google-summary img {
        width: 100px;
      }
      .google-summary-info {
        flex-direction: column;
        gap: 5px;
      }
      .slider {
        flex-direction: row;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
      }
      .slide {
        flex: 0 0 100%;
        scroll-snap-align: start;
        display: flex;
        justify-content: center;
      }
      .review-card {
        width: 90%;
        padding: 15px;
      }
      .nav-arrow {
        display: none; /* Hide navigation arrows on smaller screens */
      }
    }

    @media (max-width: 480px) {
      .reviews-header h2 {
        font-size: 1.5rem;
      }
      .rating-score {
        font-size: 1.2rem;
      }
      .stars-container .star {
        font-size: 1rem;
      }
      .review-author img {
        width: 30px;
        height: 30px;
      }
      .review-author h3 {
        font-size: 0.7rem;
      }
      .review-text {
        font-size: 0.85rem;
      }
      .read-more {
        font-size: 0.75rem;
      }
    }
  </style>
</head>
<body>
  <div class="reviews-section">
    <!-- Section Header -->
    <div class="reviews-header">
      <h2>What our customers say</h2>
      <div class="google-summary">
        <div class="google-summary-info">
          <img src="../google-reviews-logo.png" alt="Google Logo">
          <span class="rating-score"><?= number_format($overallRating, 1) ?></span>
          <div class="stars-container"><?= generateStars($overallRating) ?></div>
          <span class="total-reviews">(<?= $totalReviews ?> reviews)</span>
        </div>
        <div class="google-summary-button">
          <a class="review-button" href="https://search.google.com/local/reviews?placeid=<?= $placeId ?>" target="_blank" rel="noopener noreferrer">Review us on Google</a>
        </div>
      </div>
    </div>

    <!-- Slider Container -->
    <div class="slider-container">
      <!-- Left Navigation Arrow -->
      <button class="nav-arrow nav-left" id="prevBtn">&#10094;</button>

      <!-- Slider -->
      <div class="slider" id="slider">
        <?php foreach ($slides as $slideIndex => $slideReviews): ?>
          <div class="slide">
            <?php if (!empty($slideReviews)): ?>
              <?php foreach ($slideReviews as $review):
                $authorName = htmlspecialchars($review['author_name'] ?? 'Anonymous');
                // Truncate the name if it exceeds 11 characters
                $displayName = truncateName($authorName, 11);
                $photoUrl   = $review['profile_photo_url'] ?? 'https://via.placeholder.com/40';
                $rating     = $review['rating'] ?? 0;
                $text       = htmlspecialchars($review['text'] ?? '');
                $time       = $review['time'] ?? time();
                // Use our helper to get "X days ago"
                $daysAgo = daysAgo($time);
                // URL to the Google Reviews page for your place (individual review URLs not available)
                $reviewUrl = "https://search.google.com/local/reviews?placeid=" . $placeId;
              ?>
                <div class="review-card">
                  <div class="review-author">
                    <img src="<?= $photoUrl ?>" alt="Reviewer">
                    <div>
                      <h3 class="reviewer-name">
                        <a href="<?= $reviewUrl ?>" target="_blank" style="text-decoration:none; color: inherit;">
                          <?= $displayName ?>
                        </a>
                        <a href="<?= $reviewUrl ?>" target="_blank" style="text-decoration:none; color: inherit;">
                        <img src="../verified-badge.png" alt="Verified Badge" class="verified-badge" 
                             title="Verified reviewer on Google" 
                             style="width: 15px; height: 15px; margin-left: 5px; vertical-align: middle;"/>
                        </a>
                      </h3>
                      <span class="review-time" title="Review posted <?= $daysAgo ?>">
                        <?= $daysAgo ?>
                      </span>
                      <a class="review-google-link" href="<?= $reviewUrl ?>" target="_blank">Google</a>
                    </div>
                  </div>
                  <div class="review-rating">
                    <?= generateStars($rating) ?>
                  </div>
                  <p class="review-text"><?= $text ?></p>
                  <a class="read-more" href="<?= $reviewUrl ?>" target="_blank">Read more</a>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="review-card"><p>No review available.</p></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Right Navigation Arrow -->
      <button class="nav-arrow nav-right" id="nextBtn">&#10095;</button>
    </div>
  </div>

  <script>
    // Slider logic for navigation arrows
    const slider = document.getElementById('slider');
    const totalSlides = <?php echo $totalSlides; ?>;
    let currentSlide = 0;

    function showSlide(index) {
      if (index < 0) index = totalSlides - 1;
      if (index >= totalSlides) index = 0;
      currentSlide = index;
      slider.style.transform = 'translateX(' + (-index * (100 / totalSlides)) + '%)';
    }

    document.getElementById('prevBtn').addEventListener('click', function() {
      showSlide(currentSlide - 1);
    });

    document.getElementById('nextBtn').addEventListener('click', function() {
      showSlide(currentSlide + 1);
    });
  </script>
</body>
</html>