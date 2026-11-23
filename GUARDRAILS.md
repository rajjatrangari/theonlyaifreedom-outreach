# Outreach AI Freedom: Security & Operational Guardrails

## 1. Data Integrity & Lead Quality Guardrails
- **No Junk Leads**: Entities without an active commercial website or verified phone/WhatsApp must be filtered out or flagged as unqualified.
- **Strict Deduplication**: Duplicate business names or matching domain names are updated rather than re-inserted.
- **Safe Phone Normalization**: Phone numbers are converted to international E.164 without special characters before crafting `wa.me` links.

## 2. Platform Safety & Anti-Ban Architecture
- **Unrestricted Direct App Schemes**: Outreach uses direct user-authorized URL schemes (`https://wa.me/`, `https://linkedin.com/`, `mailto:`, `https://instagram.com/`) where the user clicks to dispatch. This prevents automated bot fingerprinting and completely avoids API suspensions.
- **Follow-up Timing**: Automatic follow-up reminders are spaced with 2 to 3 days buffer to avoid spamming prospects.

## 3. Application & Server Security
- **Data Protection**: Direct HTTP access to `data/` and `core/*.local.php` is strictly blocked by `.htaccess`.
- **Prepared Statements**: All database operations use PDO prepared statements with parameterized inputs.
- **Secure Sessions**: Session cookies enforce `HttpOnly` and `SameSite=Lax`.
- **Input Sanitization**: All user-rendered outputs pass through `h()` (`htmlspecialchars` with UTF-8).
