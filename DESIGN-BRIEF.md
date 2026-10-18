# Design brief: Reserve-A-Lab

## Audience

Source: developer, 2026-10-18.

- Requesters. They request a laboratory.
- Teachers. They approve or reject the requests assigned to them.
- Admins. They manage laboratories and inventory, and approve or reject any request.

## Primary task

Source: developer, 2026-10-18. The brief covers the whole application: all three roles and all flows.

- Requester: submit a reservation request (`forms.php`, calendar).
- Teacher and admin: approve or reject a request (`supervisor_approve.php`, `admin_approve.php`).
- Admin: manage laboratories, inventory, and reports (`admin_home.php`, `inventory.php`, `requests.php`).

## Direction

Source: developer, 2026-10-18. Redesign. The current style is a starting point only, except for the selections to keep below.

## References

Source: developer, 2026-10-18. The images in `reference_images/` show the current designs (desktop width, light mode). They record the current interface. They are not a target look, because the direction is a redesign.

| Image | Page | Inspected |
|---|---|---|
| `login.png` | Login | no |
| `requestor_home.png` | Requester home | no |
| `requestor_forms.png` | Request form | yes |
| `requestor_requests_tracker.png` | Requester request tracker | no |
| `admin_home.png` | Admin home (lab cards) | yes |
| `admin_requests_tracker.png` | Admin request tracker | no |
| `admin_inventory_tracker.png` | Admin inventory | yes |

What the inspected images show: the blue gradient header with logo, school name, centered system name and notification and profile buttons; a sub-bar with View Calendar, user name, and nav links; white cards on a light grey background; pill-shaped buttons and tabs; a blue gradient footer.

## Selections to keep

Source: developer, 2026-10-18.

1. The header banner: its color and its design (`css/header.css`, `#banner`, `.header`).
2. The school color, as the blue gradient. Current value: `--header-gradient: linear-gradient(to right, #0e0054, #0036af, #0e0054)` in `css/universal.css`.
3. Light mode as the main mode. Dark mode is a future addition: the design must allow it later, so colors must be defined as roles with a mode value and not as fixed values in components.
4. Usable on phones, tablets, and laptops. Every page and every role.
5. Breakpoints: 430px and 768px (phone up to 430px, tablet up to 768px, laptop above 768px).
6. On smaller screens, show only the important content. Secondary content is hidden or moved behind a control. The developer has not yet named which content is important on each page (see unresolved items).

Project components and assets stay in use until the developer replaces them.

## Selections to prevent (exclusions)

Source: developer, 2026-10-18.

1. The unreadable button layout in the admin inventory tab (`inventory.php`).
   Seven action buttons (Scan Barcode, Stock In, Bulk Import, Alert Thresholds, Edit Mode, Cancel, Add Product) sit in one row at line 168 to 174. The developer reports that this layout is unreadable. The cause is not measured. Only the desktop reference image (1586px, light mode) is inspected, and it shows the row as readable.

## Differences that need a decision

- None found. The keep selections and exclusions do not conflict.
- None found for "light first" and "dark later": light is the first mode, and dark mode is a later addition.

## Unresolved items

1. Which content is important on each page at 430px and 768px. Proposal for the developer to approve: keep the primary action and the main list or form, and hide descriptions and secondary columns.
2. The cause of the unreadable inventory buttons. The desktop reference image (1586px wide) shows the seven buttons in one readable row. The defect is probably at a smaller width. It is not yet measured, because the render needs an admin login (see below).
3. Rendered inspection at 430px, 768px, and laptop width. Blocked: the pages need a signed-in session for each role, and no test accounts or approved method to sign in are recorded.
4. Four reference images are not yet inspected: `login.png`, `requestor_home.png`, `requestor_requests_tracker.png`, `admin_requests_tracker.png`.
5. Content-area colors, status colors, typography, and density. Header-matching is the only color direction set.
6. Other selections to keep or prevent.
7. Dark mode palette. It is deferred by decision, not missing.

## Current style (source data, not a selection unless listed above)

Source: `css/universal.css` and `css/header.css`, read 2026-10-18. Measured from CSS, not from a rendered interface.

| Item | Current value |
|---|---|
| Header bar | padding 14px 32px, sticky, z-index 1000, shadow `0 2px 12px rgba(0,0,0,0.18)` |
| Banner | logo 60px high, school name block, white text, 14px gap |
| System name | white, 2.4rem, weight 800, uppercase, 0.06em tracking |
| Primary | `#0B1B62` |
| Primary mid | `#2B55C4` |
| Secondary | `#4F73D9` |
| Accent | `#0036af` |
| Background / surface | `#EBF0FA` / `#FFFFFF` |
| Status | success `#28a745`, danger `#dc3545`, warning `#ffc107`, info `#17a2b8` |
| Font | Inter, Raleway, system-ui, sans-serif |
| Spacing | 4, 8, 16, 24, 32 px |
| Radius | card 16 px, input 12 px, button 20 px |

## Example values

Status: `proposed`. These do not set the developer selections.

- None recorded. The developer has not asked for example values.
