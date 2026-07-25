# Xendit Sandbox Test Checklist

Use this checklist before switching YOR Vision payments from sandbox to live mode.

## Environment setup

- Add sandbox values to `vibe-app/.env`
- Set `XENDIT_SECRET_KEY` to your sandbox secret key
- Set `XENDIT_CALLBACK_TOKEN` to the callback token you configured in Xendit
- Set `XENDIT_WEBHOOK_URL` to your HTTPS webhook endpoint, for example:
  - `https://yorvision.com/api/webhooks/xendit.php`
- Set redirect URLs:
  - `XENDIT_SUCCESS_REDIRECT_URL=https://yorvision.com/`
  - `XENDIT_FAILURE_REDIRECT_URL=https://yorvision.com/`
- Keep `XENDIT_CURRENCY=PHP`

## Checkout tests

1. Successful online payment
- Choose a package
- Select `Pay Online via Xendit`
- Complete the sandbox payment
- Confirm the browser returns to the funnel
- Confirm the order status becomes `paid`
- Confirm the summary shows the correct reference, amount, and payment status

2. Failed payment
- Start an online checkout
- Use a sandbox scenario that returns failure
- Confirm the funnel shows a failed payment state
- Confirm the retry button appears

3. Expired payment
- Create an online checkout session
- Let the payment session expire, or use an expiry scenario in sandbox
- Confirm the order status becomes `expired`
- Confirm the retry button appears

4. Duplicate webhook protection
- Re-send the same webhook payload twice
- Confirm the order is updated only once
- Confirm no duplicate paid order is created

5. Invalid webhook token
- Send a webhook request with the wrong `x-callback-token`
- Confirm the endpoint returns `403`
- Confirm the order is not updated

6. Wrong amount prevention
- Change frontend totals or quantities in browser devtools
- Submit the order
- Confirm the backend still calculates the official amount from the selected package and quantity

7. Cash on Delivery fallback
- Select `Cash on Delivery`
- Submit the checkout
- Confirm the order status becomes `cod_pending`
- Confirm no Xendit checkout link is created

## Before live mode

- Replace sandbox credentials with live credentials in `.env`
- Update the webhook URL in the Xendit dashboard if needed
- Re-run the full checklist above
- Confirm HTTPS is active on the production domain
- Confirm payment logs and webhook logs are writable on the server
