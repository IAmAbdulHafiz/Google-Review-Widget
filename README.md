# Google Reviews Widget

The **Google Reviews Widget** is a PHP-based solution that fetches and displays Google Reviews for a business using the Google Places API. It provides an interactive and responsive widget that can be embedded into any website.

## Features

1. **Fetch Google Reviews**: Retrieves reviews, overall rating, and total review count from the Google Places API.
2. **Responsive Design**:
   - **Desktop**: Displays reviews in a grid slider.
   - **Mobile**: Displays reviews in an accordion layout.
3. **Interactive Elements**:
   - Displays a "Review us on Google" button.
   - Shows a verified badge next to each reviewer's name.
   - Makes reviewer names clickable, linking to their Google review.
4. **Customizable**:
   - Truncates reviewer names to 11 characters with ellipsis if too long.
   - Displays review dates as "X days ago" or "Today".
5. **Embed Support**: Easily embed the widget using an iframe.

## Prerequisites

- PHP 7.4 or higher.
- A valid Google Places API key.
- A Google Place ID for your business.

## Installation

1. Clone this repository or download the files.
   ```bash
   git clone https://github.com/yourusername/google-review-widget.git
   ```
2. Place the files in your web server's root directory (e.g., `htdocs` for XAMPP).

3. Install dependencies (if any).

## Configuration

1. Create a `.env` file in the project root (if not already present) and add your Google API key and Place ID:
   ```
   GOOGLE_API_KEY=YOUR_GOOGLE_API_KEY
   GOOGLE_PLACE_ID=YOUR_GOOGLE_PLACE_ID
   ```

2. Ensure the `config.php` file is included in the project to load environment variables.

## Usage

### Embedding the Widget

To embed the widget on your website, use the following iframe code:
```html
<iframe 
    src="http://yourdomain.com/google_review_winget/reviews-widget.php" 
    width="100%" 
    height="600" 
    frameborder="0" 
    scrolling="no">
</iframe>
```

### Customization

- Modify the `assets/css/styles.css` file to customize the appearance of the widget.
- Update the PHP logic in `reviews-widget.php` to adjust functionality as needed.

## File Structure

```
google_review_winget/
├── assets/
│   ├── css/
│   │   └── styles.css       # Widget styles
│   └── images/              # Images (e.g., Google logo, verified badge)
├── backup/
│   └── reviews-widget.php   # Backup of the main widget script
├── config.php               # Loads environment variables
├── embed-widget.html        # Example iframe embed code
├── reviews-widget.php       # Main widget script
├── .env                     # Environment variables (API key, Place ID)
└── README.md                # Project documentation
```

## Screenshots

### Desktop View
![Desktop View](assets/images/desktop-view.png)

### Mobile View
![Mobile View](assets/images/mobile-view.png)

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## Support

For issues or questions, please open an issue on the [GitHub repository](https://github.com/yourusername/google-review-widget).