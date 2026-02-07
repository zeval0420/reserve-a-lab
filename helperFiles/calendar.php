<head>
    <link rel="stylesheet" href="CalendarLibrary/event-calendar.min.css">
    <script src="CalendarLibrary/event-calendar.min.js"></script>
    <style>
        /* Overlay for modal */
        #calendarOverlay {
            display: none;
            position: fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background: rgba(0,0,0,0.6);
            z-index: 98;
        }

        /* Modal container */
        #calendarModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 10px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh; /* limit height to viewport */
            z-index: 99;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            flex-direction: column;
        }

        /* Header of modal (title + close) */
        #calendarHeader {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
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
    </style>
</head>

<div id="calendarOverlay"></div>

<div id="calendarModal">
    <div id="calendarHeader">
        <h2>ACTIVITY CALENDAR</h2>
        <button id="closeCalendarBtn">&times;</button>
    </div>

    <div id="ec"></div>
</div>

<script>
    const openCalendarBtn = document.getElementById("openCalendarBtn");
    const calendarModal = document.getElementById("calendarModal");
    const calendarOverlay = document.getElementById("calendarOverlay");
    const closeCalendarBtn = document.getElementById("closeCalendarBtn");
    
    let ec = null;
    let events_array = [];

    openCalendarBtn.addEventListener("click", () => {
        calendarModal.style.display = "flex";
        calendarOverlay.style.display = "block";

        // STEP 1 — fetch events from DB
        $.ajax({
            url: "ajax/ajax_calendar.php",
            type: "POST",
            data: { action: "get_calendar_events" },
            dataType: "json",

            success: function(response) {
                // STEP 2 — process events_array
                events_array = response; // directly replace

                // STEP 3 — calendar initialization happens AFTER ajax
                if (!ec) {
                    createCalendar(events_array);
                } else {
                    updateCalendar(events_array);
                }
            },

            error: function(xhr) {
                console.log("AJAX Error:", xhr.responseText);
                alert("There was an error in opening the calendar.");
            }
        });
    });

    function createCalendar(events) {
        setTimeout(() => {
            ec = EventCalendar.create(document.getElementById('ec'), {
                initialDate: new Date(),  // <-- start at today
                initialView: 'timeGridWeek',
                view: 'timeGridWeek',
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
                events: events_array,
                nowIndicator: true,
                eventStartEditable: false,   // cannot drag/move events
                eventDurationEditable: false // cannot resize events
            });
        }, 60);
    }
    function updateCalendar(events) {
        ec.setOption("events", events);
        ec.updateSize();
    }

    function closeModal() {
        calendarModal.style.display = "none";
        calendarOverlay.style.display = "none";
    }

    closeCalendarBtn.addEventListener("click", closeModal);
    calendarOverlay.addEventListener("click", closeModal);

    // Optional: handle window resize to update calendar
    window.addEventListener("resize", () => {
        if (ec) ec.updateSize();
    });
</script>
