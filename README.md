# Outreach AI Freedom

> **Targeted Qualified Lead Scraping, Contact Verification, AI Outreach Sequencer & Direct Multi-Channel CRM**  
> Published at: **https://outreach.theonlyaifreedom.in**

---

## 1. System Vision

Outreach AI Freedom solves the fundamental challenge of outbound sales: **finding only qualified leads with proven paying capacity**, verifying their working contacts (website, direct phone, WhatsApp, and social media footprints), and instantly dispatching personalized, high-converting outreach pitches without third-party platform bans or rigid restrictions.

### Core Capabilities
1. **User Onboarding & Business Offering Setup**:
   - Users define their business identity, niche, core offering, pricing, USP, and ideal customer profile (ICP).
   - Our AI Brain analyzes this profile alongside each prospect's web signals to generate bespoke pitches.
2. **Qualified Lead Scraping & Verification (Q-Score™ Engine)**:
   - Evaluates commercial viability across 4 pillars:
     - Commercial custom domain with live HTTPS verification (+25 pts).
     - Direct verified WhatsApp number (+25 pts).
     - Multi-channel social proof (LinkedIn + Instagram + Facebook + Twitter) (+25 pts).
     - Commercial reviews and high-intent presence (+25 pts).
   - Filters out dead websites, broken WhatsApp links, and low-budget listings.
3. **Built-in Visual Kanban CRM & Follow-up Sequencer**:
   - 12 Pipeline stages from **New Scraped** to **Closed Won 🎉**.
   - Interactive drag-and-drop board and batch review tables.
   - 4-Touch Outreach Sequencer: Initial Pitch $\rightarrow$ Day 2 Value Proof $\rightarrow$ Day 5 Quick Bump $\rightarrow$ Day 8 Closure/Breakup.
   - Due follow-up tracking with active badge notifications.
4. **Unrestricted Multi-Channel Direct Bridges**:
   - **WhatsApp**: 1-Click direct chat via `wa.me` deep link with pre-filled encoded pitch.
   - **LinkedIn**: 1-Click profile visit & InMail DM with pitch copied to clipboard.
   - **Email**: 1-Click mailto and webmail composer with custom subject and body.
   - **Instagram & Twitter**: Direct message bridges for social-first outreach.

---

## 2. Technical Architecture

- **Runtime**: PHP 8.1+ / Apache (LiteSpeed)
- **Database Engine**: Dual-mode PDO (Hostinger Dedicated MySQL `u673048014_outreach` with zero-friction SQLite fallback in `data/outreach.sqlite`).
- **Frontend**: Tailwind CSS CDN, Lucide Icons, Plus Jakarta Sans typography, custom glassmorphism components.
- **Security**: Strict `.htaccess` protection preventing direct access to `data/`, `.sqlite`, and `.local.php` files.

---

## 3. Directory Layout

```
outreach/
├── .gitignore
├── .htaccess                 # Security headers, access rules & clean URL rewrites
├── README.md                 # System overview and architecture
├── ROADMAP.md                # Feature roadmap & milestones
├── DOD.md                    # Definition of Done
├── GUARDRAILS.md             # Security and operational guardrails
├── deploy-hostinger.sh       # One-command deployment script to Hostinger
├── index.php                 # Master overview dashboard
├── scraper.php               # Qualified lead discovery & Q-Score filter
├── crm.php                   # Visual Kanban CRM & pipeline tracker
├── outreach.php              # Multi-stage AI outreach sequencer
├── channels.php              # Connected communication accounts hub
├── analytics.php             # Conversion funnels & ROI analytics
├── onboarding.php            # User offering, ICP & filter configuration
├── login.php                 # Authentication login
├── signup.php                # Account registration & initial lead seeding
├── logout.php                # Secure logout handler
├── api.php                   # AJAX endpoints for scraping, updates & dispatches
├── core/
│   ├── config.php            # Central configuration & credentials
│   ├── config.local.php.example
│   ├── bootstrap.php         # PDO connection, auto-migrations & auth
│   ├── scraper_engine.php    # Multi-source scraper & contact extractor
│   ├── qualification_engine.php # Q-Score algorithm & paying capacity tiering
│   ├── ai_generator.php      # AI copywriting & follow-up generator
│   └── layout.php            # Master responsive UI layout template
└── assets/
    ├── css/app.css           # Glassmorphism & Kanban styles
    └── js/app.js             # Drag-and-drop, modals & dispatch actions
```

---

## 4. Deployment to Hostinger

### MCP Deployment (Antigravity native)
```bash
node scripts/hostinger-cli.mjs exec hosting_deploy '{"domain":"outreach.theonlyaifreedom.in","path":"outreach"}'
```

### Script Deployment
```bash
./outreach/deploy-hostinger.sh --package-only
# or consolidated:
./scripts/deploy-all.sh outreach
```
