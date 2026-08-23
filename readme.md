# Event Listing Challenge

WordPress / PHP coding challenge: build a simple event listing.

## Requirements

**Custom post type:** Events, with fields for event date (date picker), location, event URL, a registration form, maximum attendees, current registration count, video/trailer, and banner image.

**Archive page:** List all events ordered by event date. Each item shows title, location (optional Google Maps), date, and a link to the external source. Include “Add to Google Calendar” plus one other calendar option.

**General:** Custom code (no third-party plugins for this task). Following best WordPress Coding Standards.

### Caveats

**Calendar implementation:** Google Calendar (required options) + Outlook as second option, no .ics, direct links with whole day events
**Google Maps:** Use Google Maps API key with enabled Cloud console or if not present fallback to a direct link to Google Maps
**Register form with X fields:** name, email, seats
**Extra fields:** image left out for internal WordPress banner as to avoid double work

## Setup steps

1. Copy .env.example → .env
2. docker compose up --build
3. Open the site, run the WordPress installer
4. Activate plugin
   4.1. Highly reccomended to go to Settings->Permalinks and change behaviour to anything othern than plain so plugin loads correctly
   4.2. Logic can be found at /events page
