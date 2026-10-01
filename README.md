# AI Laptop Solver landing site

    site/                             the static website: the ONLY folder that goes on the server
    FEATURES.md                       internal feature inventory. Never upload.
    landing-page-plan.md              internal: THE landing page plan (story, copy, look, SEO). Never upload.
    refund-policy-draft.md            internal: draft refund policy text for site/refund/, for Partho to edit. Never upload.
    landing-page-features-and-narrative.md   internal: older brief, superseded by landing-page-plan.md except its deploy rules. Never upload.
    NANYBOT-LANDING-GUIDE.md          step-by-step guide for the server agent to deploy site/ on nanybot.com
    SERVER-GUIDE.md                   guide for the server agent for the Laravel API only (not this site)

The site is served at https://nanybot.com/ai-laptop-solver/ :

    /ai-laptop-solver/            landing page
    /ai-laptop-solver/privacy/    privacy policy  (Partner Center "privacy policy URL")
    /ai-laptop-solver/terms/      terms of use

nanybot.com is a different project from the Laravel backend (system-care-api.nanybot.com). They share the same
VPS and the parent domain, nothing else.

All links are relative, so the folder name does not matter. Each page is `<folder>/index.html`.

## Build and deploy rules

- **The server only receives finished static files.** There is no Node, no build step and no PHP on the server.
- **Tailwind (redesign):** the CSS is compiled on Partho's PC (Tailwind CLI) into `site/assets/`, and that compiled
  file is what gets uploaded. Do not use the Tailwind CDN script in production. Tailwind's source files
  (`package.json`, `tailwind.config.*`, the input CSS, `node_modules`) stay outside `site/` and are never uploaded.
- **No third-party requests:** no external fonts, scripts, analytics or CDNs. Self-host any font inside `site/assets/`.
  A few lines of inline JavaScript for the mobile menu are fine.
- **Support email:** before upload, replace `SUPPORT_EMAIL` in `site/index.html`, `site/privacy/index.html` and
  `site/terms/index.html` with the real support address (the server agent does this in step 2 of
  NANYBOT-LANDING-GUIDE.md).
- **Privacy text:** the approved text from `system-care-backend/resources/views/privacy.blade.php` (dated
  29 September 2026). If one changes, change the other. The terms were approved by Partho on 1 October 2026.
- **Screenshots** in `site/assets/screens/` have the signed-in email and the Windows taskbar removed. The originals are
  in `custom-system-care/packaging/msix/store-screenshots/`.

## Design brief for the redesign (Partho)

Read FEATURES.md. List only the features that can be told to users and that make them think directly how they
benefit, so a "wow, fantastic!" comes out of their mouth. First extract those features from FEATURES.md and save
them in `landing-page-features-and-narrative.md`.

Then redesign the pages using Tailwind, fully responsive, including the nav bar.

The landing page must not look AI-designed. Avoid:

- Purple/blue gradient everywhere
- Huge rounded cards
- Excessive border-radius on every section, button, card and image
- Glassmorphism: translucent cards, blur, glowing borders
- Gradient text, especially purple to blue
- Dark navy background with neon purple accents
- A huge centered hero headline like "Transform Your Workflow with AI"
- Generic abstract AI blobs
- Floating 3D shapes / orbs
- Excessive shadows and glow
- Every section inside a separate card
- Pill-shaped buttons and badges everywhere
- Huge whitespace with very little actual information
- Perfectly symmetrical layouts everywhere
- AI-generated illustrations with people sitting around laptops
- Stock-looking dashboard mockups floating at an angle
- Animated gradients / particles
- "Trusted by 10,000+ users" with fake-looking logos
- Logo clouds containing obscure/random companies
- Repeated feature cards with icon + heading + 2 lines of text
- Tiny uppercase labels above every heading
- "Built for modern teams" / "The future of..." type copy
- Inter-style generic typography combined with very soft UI
- Everything looking suspiciously clean, smooth and frictionless

The bigger problem is not any single element. It is the combination: gradient + purple + rounded cards + glass +
glow + giant headline + floating UI + lots of whitespace + generic AI copy. That combination is what makes
thousands of AI-generated landing pages look almost identical.

A more distinctive landing page usually has strong typography, restrained colors, sharper geometry, real product
screenshots, dense useful information, visible product details, and fewer decorative elements.
