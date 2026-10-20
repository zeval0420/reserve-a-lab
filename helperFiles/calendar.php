<?php
// EventCalendar's CSS and JS are loaded on the first click of "View Calendar" (see loadCalendarAssets()).
$calendarCssUrl = 'CalendarLibrary/event-calendar.min.css?v=' . filemtime(__DIR__ . '/../CalendarLibrary/event-calendar.min.css');
$calendarJsUrl  = 'CalendarLibrary/event-calendar.min.js?v=' . filemtime(__DIR__ . '/../CalendarLibrary/event-calendar.min.js');
?>
    <style id="calendarStyles">
        /* Overlay for modal */
        #calendarOverlay {
            display: none;
            position: fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background: rgba(0,0,0,0.6);
            z-index: 9800;
        }

        /* Modal container */
        #calendarModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh; /* limit height to viewport */
            z-index: 9900;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            flex-direction: column;
        }

        /* Header of modal (title + close) */
        #calendarHeader {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        #calendarHeader h2 {
            margin: auto;
            font-weight: bold;
        }

        #closeCalendarBtn {
            border: none;
            background: transparent;
            font-size: 40px;
            cursor: pointer;
        }

        /* Calendar container scrollable */
        #ec {
            flex: 1; /* fill remaining modal space */
            overflow: auto;
            padding: 20px;
        }

        .ec-button {
            padding: 6px 18px !important;
            margin: 0 4px;
            border-radius: 20px !important;
            border: 1px solid rgba(43, 85, 196, 0.2) !important;
            background: linear-gradient(135deg, rgba(43, 85, 196, 0.05), rgba(43, 85, 196, 0.15)) !important;
            color: #2B55C4 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(43, 85, 196, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* Hover effect */
        .ec-button:hover {
            background: linear-gradient(135deg, rgba(43, 85, 196, 0.1), rgba(43, 85, 196, 0.25)) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(43, 85, 196, 0.2);
            color: #1a3a8f !important;
            border-color: rgba(43, 85, 196, 0.3) !important;
        }
        .ec-button.ec-active {
            background: linear-gradient(135deg, rgba(43, 85, 196, 0.2), rgba(43, 85, 196, 0.35)) !important;
            color: #1a3a8f !important;
            border-color: rgba(43, 85, 196, 0.4) !important;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }

        .ec-event-title {
            font-size: 14px !important;
            font-weight: 600;
        }

        .calendar-controls { display: flex; align-items: center; gap: 15px; }
        #calendarModal { min-height: 0; }
        #ec { min-height: 0; }

        /* Tablet and phone: near full-screen sheet, wrapped header and toolbar */
        @media (max-width: 768px) {
            #calendarModal {
                width: 100%;
                height: 96vh;
                height: 96dvh;
                max-height: none;
                top: auto;
                bottom: 0;
                left: 0;
                transform: none;
                border-radius: 20px 20px 0 0;
            }
            #calendarHeader { flex-wrap: wrap; gap: 8px; padding: 10px 12px; }
            #calendarHeader h2 { order: 1; margin: 0; flex: 1 1 auto; font-size: 20px; text-align: left; }
            #closeCalendarBtn { order: 2; font-size: 32px; line-height: 1; padding: 0 4px; }
            .calendar-controls { order: 3; width: 100%; flex-wrap: wrap; gap: 8px; }
            .calendar-controls #calendarLabFilter { flex: 1 1 100%; min-width: 0; }
            .calendar-controls #calendarDatePicker { flex: 1 1 auto; min-width: 0; }
            #ec { padding: 8px; }

            /* event-calendar toolbar: title on top, then buttons wrap */
            #ec .ec-toolbar { flex-wrap: wrap; justify-content: center; gap: 6px; }
            #ec .ec-toolbar > :nth-child(2) { order: -1; flex: 1 1 100%; text-align: center; }
            #ec .ec-title { font-size: 1.15rem; }
            #ec .ec-button { padding: 5px 12px !important; margin: 0 2px; }
        }
        @media (max-width: 430px) {
            .ec-event-title { font-size: 12px !important; }
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; display: inline-block; }

        /* Event Details Modal */
        #eventDetailsModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        #eventDetailsContent {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        #closeEventDetails {
            position: absolute;
            top: 15px;
            right: 20px;
            border: none;
            background: transparent;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        #closeEventDetails:hover { color: #000; }
    </style>

<div id="calendarOverlay"></div>

<div id="calendarModal">
    <div id="calendarHeader">
        <h2>ACTIVITY CALENDAR</h2>
        <div class="calendar-controls">
            <button id="calendarTodayBtn" class="btn-liquid" style="padding: 6px 12px;">Today</button>
            <input type="date" id="calendarDatePicker" class="liquid-input" style="padding: 5px 10px; font-size: 13px; cursor: pointer;">
            <select id="calendarLabFilter" class="liquid-input" style="padding: 6px 30px 6px 12px; font-size: 13px; cursor: pointer; min-width: 150px;">
                <option value="all">All Laboratories</option>
            </select>
        </div>
        <button id="closeCalendarBtn" aria-label="Close calendar">&times;</button>
    </div>

    <div id="ec"></div>
</div>

<div id="eventDetailsModal">
    <div id="eventDetailsContent">
        <button id="closeEventDetails">&times;</button>
        <h3 id="eventTitle" style="margin-top:0; color:#0e0054; border-bottom:1px solid rgba(0,0,0,0.1); padding-bottom:10px; margin-bottom:15px;"></h3>
        <div id="eventBody"></div>
    </div>
</div>

<script>
    const openCalendarBtn = document.getElementById("openCalendarBtn");
    const calendarModal = document.getElementById("calendarModal");
    const calendarOverlay = document.getElementById("calendarOverlay");
    const closeCalendarBtn = document.getElementById("closeCalendarBtn");
    const currentUserRole = "<?php echo $_SESSION['role'] ?? ''; ?>";
    
    let ec = null;
    let events_array = [];

    // EventCalendar's CSS and JS are loaded once, on the first click of "View Calendar".
    // The stylesheet is inserted where its <link> used to be (just before #calendarStyles),
    // so the cascade order is the same as when it was loaded with the page.
    let calendarCssPromise = null;
    let calendarJsPromise = null;
    function loadCalendarAssets() {
        if (!calendarCssPromise) {
            calendarCssPromise = new Promise((done) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = <?= json_encode($calendarCssUrl) ?>;
                link.onload = link.onerror = () => done();
                const styles = document.getElementById('calendarStyles');
                styles.parentNode.insertBefore(link, styles);
            });
        }
        if (!calendarJsPromise) {
            calendarJsPromise = (typeof EventCalendar !== 'undefined')
                ? Promise.resolve()
                : new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = <?= json_encode($calendarJsUrl) ?>;
                    script.onload = () => resolve();
                    script.onerror = () => {
                        script.remove();
                        calendarJsPromise = null; // allow a retry on the next click
                        reject(new Error('Could not load the calendar library'));
                    };
                    document.head.appendChild(script);
                });
        }
        return Promise.all([calendarCssPromise, calendarJsPromise]);
    }

    openCalendarBtn.addEventListener("click", () => {
        calendarModal.style.display = "flex";
        calendarOverlay.style.display = "block";

        // Start loading the calendar library while the events are fetched
        loadCalendarAssets().catch(() => { /* reported by createCalendar() */ });

        // Set date picker to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('calendarDatePicker').value = today;

        // STEP 1 — fetch events from DB
        $.ajax({
            url: "ajax/ajax_calendar.php",
            type: "POST",
            data: { action: "get_calendar_events" },
            dataType: "json",

            success: function(response) {
                // STEP 2 — process events_array
                events_array = response; // directly replace
                populateLabFilter(events_array);
                applyLabFilter();
            },

            error: function(xhr) {
                console.log("AJAX Error:", xhr.responseText);
                showToast("There was an error in opening the calendar.", 'error');
            }
        });
    });

    function populateLabFilter(events) {
        const filter = document.getElementById('calendarLabFilter');
        const current = filter.value;
        // Extract unique lab names (titles)
        const labs = [...new Set(events.map(e => e.title))].sort();
        
        filter.innerHTML = '<option value="all">All Laboratories</option>';
        
        labs.forEach(lab => {
            const opt = document.createElement('option');
            opt.value = lab;
            opt.textContent = lab;
            filter.appendChild(opt);
        });
        
        if (labs.includes(current)) filter.value = current;
    }

    function applyLabFilter() {
        const selected = document.getElementById('calendarLabFilter').value;
        const filtered = selected === 'all' 
            ? events_array 
            : events_array.filter(e => e.title === selected);

        if (!ec) {
            createCalendar(filtered);
        } else {
            updateCalendar(filtered);
        }
    }

    document.getElementById('calendarLabFilter').addEventListener('change', applyLabFilter);

    document.getElementById('calendarDatePicker').addEventListener('change', function() {
        if (ec && this.value) {
            ec.setOption('date', this.value);
        }
    });

    document.getElementById('calendarTodayBtn').addEventListener('click', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('calendarDatePicker').value = today;
        if (ec) {
            ec.setOption('date', today);
        }
    });

    // While a calendar's creation is scheduled (library loading + the 60 ms delay), further calls
    // only replace the events it will be created with, so a second calendar is never created.
    let ecCreationPending = false;
    let ecPendingEvents = [];

    function createCalendar(events) {
        ecPendingEvents = events;
        if (ecCreationPending) return;
        ecCreationPending = true;

        loadCalendarAssets().then(() => {
            setTimeout(() => {
                ecCreationPending = false;
                ec = EventCalendar.create(document.getElementById('ec'), {
                    initialDate: new Date(),  // <-- start at today
                    // a seven-column week grid is unreadable on a phone, so start on the day view there
                    initialView: window.innerWidth <= 430 ? 'timeGridDay' : 'timeGridWeek',
                    scrollTime: '06:00:00',
                    view: window.innerWidth <= 430 ? 'timeGridDay' : 'timeGridWeek',
                    headerToolbar: {
                        start: 'dayGridMonth,timeGridWeek,timeGridDay',
                        center: 'title',
                        end: 'today prev,next'
                    },
                    buttonText: {
                        today: 'Today',
                        dayGridMonth: 'Month',
                        timeGridWeek: 'Week',
                        timeGridDay: 'Day'
                    },
                    events: ecPendingEvents,
                    nowIndicator: true,
                    eventStartEditable: false,   // cannot drag/move events
                    eventDurationEditable: false, // cannot resize events
                    eventClick: function(info) {
                        showEventDetails(info.event.id);
                    }
                });
            }, 60);
        }, (err) => {
            ecCreationPending = false;
            console.log("Calendar library error:", err);
            showToast("There was an error in opening the calendar.", 'error');
        });
    }
    // EventCalendar v4 sizes itself with CSS (it has no updateSize()), so setting the events is enough.
    function updateCalendar(events) {
        ec.setOption("events", events);
    }

    function closeModal() {
        calendarModal.style.display = "none";
        calendarOverlay.style.display = "none";
    }

    closeCalendarBtn.addEventListener("click", closeModal);
    calendarOverlay.addEventListener("click", closeModal);

    const eventDetailsModal = document.getElementById('eventDetailsModal');
    const closeEventDetails = document.getElementById('closeEventDetails');
    const eventBody = document.getElementById('eventBody');
    const eventTitle = document.getElementById('eventTitle');

    function showEventDetails(id) {
        eventDetailsModal.style.display = 'flex';
        eventBody.innerHTML = '<div style="text-align:center; padding:20px;"><i class="bi bi-arrow-repeat spin" style="font-size:2rem; color:#2B55C4;"></i></div>';
        eventTitle.innerText = 'Request Details';

        $.ajax({
            url: "ajax/ajax_calendar.php",
            type: "POST",
            data: { action: "get_request_details", id: id },
            dataType: "json",
            success: function(data) {
                if(data.error) {
                    eventBody.innerHTML = '<p class="text-danger">Details not found.</p>';
                    return;
                }
                
                eventTitle.innerText = data.scilabName;

                let buttonsHtml = '';
                if (data.statusScilabPersonnel === 'Approved') {
                    buttonsHtml += `<a href="templates/print_template.php?id=${encodeURIComponent(data.id)}" target="_blank" class="btn-liquid" style="margin-right: 5px;"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>`;
                }

                if (currentUserRole === 'admin') {
                    buttonsHtml += `<a href="admin_approve.php?status=Approved&search=${encodeURIComponent(data.id)}" class="btn-liquid">View Full Request</a>`;
                }

                if (buttonsHtml) {
                    buttonsHtml = `<div style="margin-top:15px; text-align:right;">${buttonsHtml}</div>`;
                }

                eventBody.innerHTML = `
                    <p><strong>Requester:</strong> ${ralEsc(data.requesterName || data.requesterEmployeeID)}</p>
                    <p><strong>Subject:</strong> ${ralEsc(data.subject)}</p>
                    <p><strong>Topic:</strong> ${ralEsc(data.subjectTopic)}</p>
                    <p><strong>Date:</strong> ${ralEsc(data.inclusiveDate)}</p>
                    <p><strong>Time:</strong> ${ralEsc(data.inclusiveTime)}</p>
                    <p><strong>Status:</strong> <span class="badge" style="background-color:#28a745;">${ralEsc(data.statusScilabPersonnel)}</span></p>
                    ${buttonsHtml}
                `;
            },
            error: function() {
                eventBody.innerHTML = '<p class="text-danger">Failed to load details.</p>';
            }
        });
    }

    closeEventDetails.addEventListener('click', () => eventDetailsModal.style.display = 'none');
    eventDetailsModal.addEventListener('click', (e) => { if(e.target === eventDetailsModal) eventDetailsModal.style.display = 'none'; });
</script>
