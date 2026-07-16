# YOR VISION FAQ Editing Guide

Edit the public FAQ content here:

`public/data/faqs.json`

Update each item inside the `faqs` array:

- `id`: Keep the numbers 1 through 20.
- `category`: Choose one of the existing category names.
- `question`: Replace this with the public FAQ question.
- `answer`: Replace this with the complete public answer.

Keep `schemaEnabled` set to `false` until all FAQ content is final, public, and verified. Turn it on only when the page should publish FAQPage structured data.

After editing, upload the updated `public/data/faqs.json` file with the site files. The FAQ section will load the changes automatically.
