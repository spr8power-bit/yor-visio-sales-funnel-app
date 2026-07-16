# Customer Stories & Product Reviews Guide

This project currently uses a shared-hosting friendly static data file for testimonials.

## Where To Upload Images

Place approved testimonial screenshots, customer photos, or product feedback images in:

`public/images/yor-vision/testimonials`

Use JPG, JPEG, PNG, or WebP. Keep screenshots readable and avoid aggressive compression.

## Where To Add Reviews

Edit:

`public/data/testimonials.json`

Use this structure:

```json
{
  "id": "review-001",
  "type": "message_screenshot",
  "customerName": "First name or approved display name",
  "customerLocation": "City or province only",
  "customerPhoto": "",
  "testimonialImage": "/images/yor-vision/testimonials/review-001.webp",
  "reviewText": "Approved review text or transcript.",
  "rating": null,
  "reviewDate": "2026-07-13",
  "packageName": "Daily Wellness Pack",
  "isVerified": false,
  "isFeatured": true,
  "isPublished": true,
  "displayOrder": 1,
  "consentConfirmed": true,
  "complianceStatus": "approved",
  "altText": "Customer testimonial screenshot for YOR VISION Mineral Drops."
}
```

## Publishing Rules

Only approved testimonials appear publicly. The site hides testimonials unless:

- `isPublished` is `true`
- `consentConfirmed` is `true`
- `complianceStatus` is `"approved"`

Do not publish screenshots that contain phone numbers, email addresses, home addresses, order numbers, private account details, or unsupported medical claims.

## Compliance Notes

Do not publish testimonials claiming that YOR VISION cures, treats, restores vision, removes cataracts, treats glaucoma, or guarantees results. Keep those entries unpublished until reviewed.
