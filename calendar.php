<?php
include 'auth.php';
include '../database.php';

$canEdit    = in_array($_SESSION['role'] ?? '', ['Admin','Staff']);
$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
$pageTitle  = 'Calendar';
$activePage = 'calendar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Calendar</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Patrick+Hand&display=swap">
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/fullcalendar/main.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
/* =====================================================
   CALENDAR – PASTEL MAGENTA THEME (NO BLUE GUARANTEED)
===================================================== */
:root{
  --magenta:#ff0090;
  --magenta-strong:#e60080;
  --magenta-soft:#f49ac2;
  --magenta-pastel:#fde3ee;
  --magenta-glass:rgba(255,0,144,.08);
  --salmon:#ff9999;
}

/* =====================================================
   KILL ALL DEFAULT BLUE (IMPORTANT)
===================================================== */
.bg-primary,
.bg-info,
.bg-success,
.bg-warning,
.bg-danger,
.btn-info,
.btn-success,
.btn-warning,
.btn-primary,
.badge-info,
.badge-primary,
.text-info{
  background-color:var(--magenta) !important;
  border-color:var(--magenta) !important;
  color:#fff !important;
}

/* remove bootstrap focus blue */
*:focus{
  box-shadow:none !important;
  outline:none !important;
}

/* =====================================================
   PAGE TITLE
===================================================== */
.content-header h1{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   CARD BASE
===================================================== */
.card{
  border-top:3px solid var(--magenta);
}
.card-header{
  border-bottom:1px solid var(--magenta-soft);
}
.card-title{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   INPUT
===================================================== */
.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .15rem rgba(255,0,144,.25) !important;
}

/* =====================================================
   BUTTONS
===================================================== */
.btn-primary{
  background:var(--magenta);
  border-color:var(--magenta);
}
.btn-primary:hover{
  background:var(--magenta-strong);
  border-color:var(--magenta-strong);
}

.btn-secondary{
  background:transparent;
  border:1px solid var(--magenta);
  color:var(--magenta);
}
.btn-secondary:hover{
  background:var(--magenta-soft);
  color:#000;
}

.btn-danger{
  background:transparent;
  border:1px solid var(--salmon);
  color:var(--salmon);
}
.btn-danger:hover{
  background:var(--salmon);
  color:#000;
}

/* =====================================================
   DRAGGABLE TEMPLATE EVENTS (LEFT) – NO BLUE
===================================================== */
.external-event{
  background:linear-gradient(
    90deg,
    rgba(255,0,144,.55),
    rgba(244,154,194,.85)
  ) !important;
  border:1.5px solid var(--magenta);
  color:#fff !important;
  padding:10px 14px;
  margin-bottom:10px;
  border-radius:14px;
  font-weight:600;
  cursor:grab;
  box-shadow:0 6px 18px rgba(255,0,144,.35);
}
.external-event:hover{
  opacity:.92;
}

/* delete icon */
.external-event .t-actions button{
  background:rgba(255,255,255,.15);
  border:1px solid #fff;
  color:#fff;
}
.external-event .t-actions button:hover{
  background:#fff;
  color:var(--magenta);
}

/* =====================================================
   FULLCALENDAR BASE
===================================================== */
.fc{
  background:#fff;
}

/* MONTH TITLE */
.fc-toolbar-title{
  color:var(--magenta);
  font-weight:700;
}

/* WEEKDAY HEADER */
.fc-col-header-cell{
  background:var(--magenta-glass);
}
.fc-col-header-cell-cushion{
  color:var(--magenta);
  font-weight:600;
}

/* DAY CELL */
.fc-daygrid-day{
  background:#fff;
}
.fc-daygrid-day:hover{
  background:rgba(255,0,144,.06);
}

/* DATE NUMBER */
.fc-daygrid-day-number{
  color:var(--magenta) !important;
  font-weight:600;
}

/* TODAY */
.fc-day-today{
  background:rgba(255,0,144,.16) !important;
}
.fc-day-today .fc-daygrid-day-number{
  color:var(--magenta-strong) !important;
  font-weight:700;
}

/* =====================================================
   EVENTS ON CALENDAR – FORCE MAGENTA
===================================================== */
.fc-event,
.fc-h-event,
.fc-v-event{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  ) !important;
  border:none !important;
  border-radius:14px;
  padding:4px 10px;
  font-weight:600;
  box-shadow:0 4px 12px rgba(255,0,144,.35);
}

.fc-event-title{
  color:#fff !important;
}

/* remove FullCalendar blue active */
.fc-event:focus,
.fc-event-selected{
  box-shadow:0 0 0 2px rgba(255,0,144,.35) !important;
}

/* =====================================================
   NAV BUTTONS
===================================================== */
.fc-today-button{
  background:var(--magenta) !important;
  border-color:var(--magenta) !important;
  color:#fff !important;
}
.fc-today-button:hover{
  background:var(--magenta-strong) !important;
}

.fc-prev-button,
.fc-next-button{
  background:transparent !important;
  border:1.5px solid var(--magenta) !important;
  color:var(--magenta) !important;
}
.fc-prev-button:hover,
.fc-next-button:hover{
  background:var(--magenta-soft) !important;
  color:#000 !important;
}

.fc-prev-button .fc-icon,
.fc-next-button .fc-icon{
  color:var(--magenta) !important;
}

/* =====================================================
   STICKY NOTE MODAL – PERFECT SIZE & POSITION
===================================================== */
.sticky-modal .modal-dialog{
  max-width:820px;
  margin:2.5rem auto;
}

.sticky-modal .modal-content{
  background:var(--magenta-pastel);
  border-radius:20px;
  border:2px solid var(--magenta-soft);
  box-shadow:0 22px 55px rgba(255,0,144,.4);
}

.sticky-modal .modal-header{
  background:rgba(255,255,255,.6);
  border-bottom:1px dashed var(--magenta-soft);
}

.sticky-modal .modal-title{
  font-family:'Patrick Hand',cursive;
  font-size:26px;
  color:var(--magenta);
}

.sticky-date{
  color:#555;
}

/* textarea */
.sticky-modal textarea{
  width:100%;
  min-height:280px;
  max-height:420px;
  resize:vertical;
  background:rgba(255,255,255,.85);
  border:2px dashed var(--magenta);
  border-radius:16px;
  padding:18px;
  font-family:'Patrick Hand',cursive;
  font-size:20px;
}

/* =====================================================
   DRAG GHOST (WHEN DRAGGING EVENT)
===================================================== */
.fc-event-dragging,
.fc-event-mirror{
  background:linear-gradient(
    90deg,
    var(--magenta-strong),
    var(--magenta-soft)
  ) !important;
  opacity:.9 !important;
}


</style>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $themeClass ?>">
<div class="wrapper">

<?php include 'partials/navbar.php'; include 'partials/sidebar.php'; ?>

<div class="content-wrapper">
<section class="content-header">
  <div class="container-fluid"><h1>Calendar</h1></div>
</section>

<section class="content">
<div class="container-fluid">
<div class="row">

<!-- LEFT -->
<div class="col-md-3">
<?php if($canEdit){ ?>
  <div class="card">
    <div class="card-header"><h4 class="card-title">Draggable Events</h4></div>
    <div class="card-body">
      <div id="external-events"></div>
      <small class="text-muted d-block mt-2">Hover = hint · Click = sticky note</small>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h4 class="card-title">Create Template</h4></div>
    <div class="card-body">
      <div class="input-group">
        <input id="new-template" class="form-control" placeholder="Template title">
        <div class="input-group-append">
          <button id="btn-add-template" class="btn btn-primary" type="button">Add</button>
        </div>
      </div>
    </div>
  </div>
<?php } ?>
</div>

<!-- CALENDAR -->
<div class="col-md-9">
  <div class="card card-primary">
    <div class="card-body p-2">
      <div id="calendar"></div>
    </div>
  </div>
</div>

</div>
</div>
</section>
</div>

<!-- ===== STICKY NOTE MODAL ===== -->
<div class="modal fade sticky-modal" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:650px">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h5 id="modalTitle" class="modal-title"></h5>
          <div id="modalDate" class="sticky-date"></div>
        </div>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body">
        <textarea id="modalNotes"
          placeholder="- Example: meeting 2pm&#10;- bring document"></textarea>
			 

      </div>

      <div class="modal-footer">
        <?php if($canEdit){ ?>
          <!-- IMPORTANT: type=button -->
          <button id="btnSaveNote" type="button" class="btn btn-primary btn-sm">Save</button>
          <button id="btnDeleteEvent" type="button" class="btn btn-danger btn-sm">Delete</button>
        <?php } ?>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/fullcalendar/main.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script>
$(function(){

const canEdit = <?= $canEdit ? 'true' : 'false' ?>;
let templateDraggable = null;
let selectedEvent = null;

function ajaxPost(url, data, onOk){
  $.ajax({
    url: url,
    method: 'POST',
    data: data,
    dataType: 'json'
  })
  .done(function(res){
    if(res && res.success){
      onOk(res);
    } else {
      alert('Request failed:\n' + JSON.stringify(res));
    }
  })
  .fail(function(xhr){
    alert('Request failed:\n' + (xhr.responseText || 'Unknown error'));
  });
}

function loadTemplates(){
  if(!canEdit) return;

  $.get('calendar_api/fetch_events.php', { mode:'templates' }, function(html){
    $('#external-events').html(html);

    if(templateDraggable){
      try{ templateDraggable.destroy(); }catch(e){}
    }

    templateDraggable = new FullCalendar.Draggable(
      document.getElementById('external-events'),
      {
        itemSelector: '.external-event',
        eventData: function(el){
          return {
            title: el.dataset.title || '',
            allDay: true
          };
        }
      }
    );

    $('.btn-del-template').off().on('click', function(){
      if(!confirm('Delete this template?')) return;

      ajaxPost('calendar_api/delete_event.php',{
        id: $(this).data('id'),
        type:'template'
      }, loadTemplates);
    });
  }, 'html');
}

$('#btn-add-template').on('click', function(){
  const title = $('#new-template').val().trim();
  if(!title) return;

  ajaxPost('calendar_api/add_event.php',{
    mode:'template',
    title:title
  }, function(){
    $('#new-template').val('');
    loadTemplates();
  });
});

const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
  initialView: 'dayGridMonth',
  editable: canEdit,
  droppable: canEdit,
  timeZone: 'local', // ✅ IMPORTANT
  displayEventTime: false, // 🔥 BUANG "12a"
  events: 'calendar_api/fetch_events.php?mode=events',

  /* ✅ DROP: ALWAYS CREATE ALLDAY EVENT (prevents date shift) */
  eventReceive: function(info){
    ajaxPost('calendar_api/add_event.php',{
      mode: 'drop',
      title: info.event.title,
      start: info.event.startStr,     // "YYYY-MM-DD"
      allDay: 1,                      // ✅ FORCE allDay
      notes: ''
    }, function(res){
      if(res && res.id){
        info.event.setProp('id', String(res.id));
        info.event.setAllDay(true);                   // ✅ keep allDay
        info.event.setExtendedProp('notes', '');
      } else {
        info.event.remove();
        alert('Failed to save event');
      }
    });
  },

  /* ✅ WHEN DRAG MOVE EVENT ON CALENDAR: update date-only */
  eventDrop: function(info){
    ajaxPost('calendar_api/update_event.php', {
      id: info.event.id,
      start: info.event.startStr, // ✅ date-only "YYYY-MM-DD"
      allDay: 1
    }, function(){});
  },

  eventDidMount: function(info){
    $(info.el).popover({
      content: 'Click here',
      trigger: 'hover',
      placement: 'top',
      container: 'body'
    });
  },

  eventClick: function(info){
    selectedEvent = info.event;

    $('#modalTitle').text(info.event.title || '');
    $('#modalDate').text(
      info.event.start
        ? info.event.start.toLocaleDateString(undefined,{
            weekday:'long',year:'numeric',month:'long',day:'numeric'
          })
        : ''
    );

    $('#modalNotes').val(info.event.extendedProps.notes || '');
    $('#eventModal').modal('show');
  }
});

calendar.render();
if(canEdit) loadTemplates();

/* ✅ SAVE NOTE: update notes only (no messing with start/allDay) */
$('#btnSaveNote').on('click', function(e){
  e.preventDefault();
  e.stopPropagation();

  if(!selectedEvent || !selectedEvent.id){
    alert('Invalid event');
    return;
  }

  const notesVal  = $('#modalNotes').val();

  ajaxPost('calendar_api/update_event.php',{
    id: selectedEvent.id,
    notes: notesVal
  }, function(){
    selectedEvent.setExtendedProp('notes', notesVal);
    $('#eventModal').modal('hide');
  });
});

/* DELETE */
$('#btnDeleteEvent').on('click', function(e){
  e.preventDefault();
  e.stopPropagation();

  if(!selectedEvent || !selectedEvent.id){
    alert('Invalid event');
    return;
  }

  if(!confirm('Delete this event?')) return;

  ajaxPost('calendar_api/delete_event.php',{
    id: selectedEvent.id,
    type:'event'
  }, function(){
    selectedEvent.remove();
    $('#eventModal').modal('hide');
  });
});

$('#eventModal').on('hidden.bs.modal', function(){
  selectedEvent = null;
  $('#modalTitle').text('');
  $('#modalNotes').val('');
  $('#modalDate').text('');
});

});
</script>





</body>
</html>
