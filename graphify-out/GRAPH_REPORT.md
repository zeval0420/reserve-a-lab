# Graph Report - reserve-a-lab  (2026-10-18)

## Corpus Check
- Large corpus: 936 files · ~6,766,166 words. Semantic extraction will be expensive (many Claude tokens). Consider running on a subfolder.

## Summary
- 348 nodes · 454 edges · 61 communities (19 shown, 42 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 28 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Supervisor Approval Actions
- Supervisor Approval Page
- Request Form UI
- Server DataTable
- Laboratory Cards
- Admin Actions & Mailer
- Calendar
- Admin Home
- Inventory
- Requester Home
- Composer Config
- Flipbook Navigation
- Footer Notifications
- Login Page
- Flipbook Controller
- Fullscreen Control
- PDF Renderer
- Guidance Login
- Dashboard
- Requests Report
- Flipbook Main
- Inventory Helpers
- Page-turn Sound
- Community 29

## God Nodes (most connected - your core abstractions)
1. `ServerDataTable` - 21 edges
2. `_escapeHtml()` - 11 edges
3. `_buildListCard()` - 10 edges
4. `_buildCompleteCard()` - 8 edges
5. `_buildCompactCard()` - 8 edges
6. `scilab_notify_unresolved_stage()` - 8 edges
7. `scilab_resolve_requester_name()` - 7 edges
8. `scilab_send_status_email()` - 6 edges
9. `scilab_approval_token()` - 6 edges
10. `sendNotificationToCIDChief()` - 5 edges

## Surprising Connections (you probably didn't know these)
- `sendSubmissionNotificationToSupervisors()` --calls--> `scilab_approval_token()`  [INFERRED]
  ajax/ajax_forms.php → helperFiles/variableDeclarations.php
- `sendSubmissionNotificationToSupervisors()` --calls--> `scilab_approval_token()`  [INFERRED]
  ajax/ajax_supervisor_action.php → helperFiles/variableDeclarations.php
- `guidance_send_review_notification()` --calls--> `scilab_send_status_email()`  [INFERRED]
  fil/guidance/referral/emailHelper.php → helperFiles/scilab_email.php
- `sendNotificationToAdmins()` --calls--> `scilab_notify_unresolved_stage()`  [INFERRED]
  ajax/ajax_supervisor_action.php → helperFiles/scilab_email.php
- `sendNotificationToAdmins()` --calls--> `scilab_resolve_requester_name()`  [INFERRED]
  ajax/ajax_supervisor_action.php → helperFiles/scilab_email.php

## Import Cycles
- None detected.

## Communities (61 total, 42 thin omitted)

### Community 0 - "Supervisor Approval Actions"
Cohesion: 0.11
Nodes (23): sendNotificationToAdmins(), sendNotificationToCIDChief(), sendNotificationToSubjectTeacher(), sendSubmissionNotificationToSupervisors(), guidance_admin_emails(), guidance_send_review_notification(), guidance_send_submission_notification(), {closure#2}() (+15 more)

### Community 1 - "Supervisor Approval Page"
Cohesion: 0.10
Nodes (21): approvalToken, closeModal(), closeTopBtn, executeAction(), handleApprove(), hideRejectArea(), modal, modalFooter (+13 more)

### Community 2 - "Request Form UI"
Cohesion: 0.11
Nodes (16): allStudents, applyAvailability(), buildItemOptions(), CLASSIFICATIONS, $container, createRowHtml(), defaultRows(), getAvailableQty() (+8 more)

### Community 4 - "Laboratory Cards"
Cohesion: 0.25
Nodes (16): _actionButtons(), _availabilityBadge(), _buildCompactCard(), _buildCompleteCard(), _buildListCard(), _colorBadge(), _colorBar(), createLabCard() (+8 more)

### Community 5 - "Admin Actions & Mailer"
Cohesion: 0.17
Nodes (3): createMailer(), sendSubmissionNotificationToAdmins(), sendSubmissionNotificationToSupervisors()

### Community 6 - "Calendar"
Cohesion: 0.15
Nodes (13): applyLabFilter(), calendarModal, calendarOverlay, closeCalendarBtn, closeEventDetails, createCalendar(), eventBody, eventDetailsModal (+5 more)

### Community 7 - "Admin Home"
Cohesion: 0.19
Nodes (10): galleryImages, _injectAdminControls(), labsData, nextImage(), prevImage(), _refreshToggles(), renderGallery(), showGallery() (+2 more)

### Community 8 - "Inventory"
Cohesion: 0.16
Nodes (6): CLASSIFICATIONS, exitEditMode(), handleScannedBarcode(), renderTable(), saveBulkChanges(), startScanner()

### Community 9 - "Requester Home"
Cohesion: 0.22
Nodes (13): escapeHtml(), formatDateLabel(), galleryImages, labsData, nextImage(), prevImage(), renderGallery(), renderSlotList() (+5 more)

### Community 10 - "Composer Config"
Cohesion: 0.22
Nodes (8): authors, autoload, psr-4, description, name, Vendor\\AutoloadPhp\\, require, dompdf/dompdf

### Community 11 - "Flipbook Navigation"
Cohesion: 0.42
Nodes (8): bindButtons(), bindKeyboard(), bindPageChangeSync(), formatIndicator(), initNavigationControls(), isTypingContext(), updateButtonState(), updateIndicator()

### Community 12 - "Footer Notifications"
Cohesion: 0.39
Nodes (8): addToNotificationHistory(), clearNotifications(), filterNotifications(), markNotificationsAsRead(), renderNotificationHistory(), showToast(), toggleNotificationSidebar(), updateNotificationBadge()

### Community 13 - "Login Page"
Cohesion: 0.29
Nodes (3): clearAlerts(), loginRedirect, showAlert()

### Community 15 - "Flipbook Controller"
Cohesion: 0.60
Nodes (5): computeSizeBounds(), createFlipbook(), emitPageChange(), prefersReducedMotion(), syncCoverResting()

### Community 16 - "Fullscreen Control"
Cohesion: 0.67
Nodes (5): initFullscreenControl(), isCurrentlyFullscreen(), isFullscreenSupported(), toggleFullscreen(), updateButtonLabel()

### Community 17 - "PDF Renderer"
Cohesion: 0.47
Nodes (3): bootstrap(), renderPageToImage(), renderPdfToImages()

### Community 19 - "Dashboard"
Cohesion: 0.47
Nodes (3): badges(), esc(), listItems()

### Community 20 - "Requests Report"
Cohesion: 0.47
Nodes (5): formatDate(), initModal(), initSummaryReport(), loadReportStats(), table

### Community 23 - "Flipbook Main"
Cohesion: 1.00
Nodes (3): getStage(), mountBook(), showError()

## Knowledge Gaps
- **42 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `scilab_approval_token()` connect `Supervisor Approval Actions` to `Inventory Helpers`, `Admin Actions & Mailer`?**
  _High betweenness centrality (0.004) - this node is a cross-community bridge._
- **Should `Supervisor Approval Actions` be split into smaller, more focused modules?**
  _Cohesion score 0.10873440285204991 - nodes in this community are weakly interconnected._
- **Should `Supervisor Approval Page` be split into smaller, more focused modules?**
  _Cohesion score 0.09846153846153846 - nodes in this community are weakly interconnected._
- **Should `Request Form UI` be split into smaller, more focused modules?**
  _Cohesion score 0.10666666666666667 - nodes in this community are weakly interconnected._