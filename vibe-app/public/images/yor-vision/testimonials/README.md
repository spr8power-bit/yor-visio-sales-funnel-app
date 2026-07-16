# YOR VISION Testimonial Images

Upload approved testimonial images here.

Supported formats:
- JPG
- JPEG
- PNG
- WebP

Recommended workflow:
1. Review the image for private information before upload.
2. Remove phone numbers, email addresses, home addresses, order numbers, and private account details.
3. Save a web-friendly copy in this folder.
4. Add the testimonial entry to `public/data/testimonials.json`.
5. Only set `isPublished`, `consentConfirmed`, and `complianceStatus: "approved"` after review.

Public testimonials are only shown when all of these are true:
- `isPublished`
- `consentConfirmed`
- `complianceStatus` is `approved`
