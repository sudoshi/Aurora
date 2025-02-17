import React, { useState, useEffect } from 'react';
import { AlertTriangle, Activity, Stethoscope, Users, CalendarDays, Brain, Heart, Microscope } from 'lucide-react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import EventModal from './EventModal';

const EventCard = ({ event, onClick, isSelected }) => {
  const categoryColors = {
    'icu': 'red',
    'surgical': 'purple',
    'oncology': 'blue',
    'neurology': 'teal',
    'admin': 'gray',
    'multidisciplinary': 'green'
  };

  const categoryIcons = {
    'icu': Activity,
    'surgical': Heart,
    'oncology': Microscope,
    'neurology': Brain,
    'admin': Users,
    'multidisciplinary': Stethoscope
  };

  const Icon = categoryIcons[event.category];
  const color = categoryColors[event.category];

  return (
    <div 
      onClick={() => onClick(event)}
      className={`
        absolute left-1 right-1 p-2 rounded-lg
        bg-${color}-900/50 border border-${color}-500/50
        hover:bg-${color}-900/70 cursor-pointer
        transition-all duration-200
        ${isSelected ? `ring-2 ring-${color}-500 bg-${color}-900/70` : ''}
        z-10
      `}
      style={{
        top: `${(event.startHour * 80) - 2}px`,
        height: `${event.durationHours * 80 - 4}px`,
      }}
    >
      <div className="flex items-start gap-2 h-full">
        <div className={`p-1.5 rounded-lg bg-${color}-900/50 text-${color}-400 shrink-0`}>
          <Icon className="w-4 h-4" />
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <span className={`w-2 h-2 rounded-full bg-${color}-400`} />
            <h4 className="text-sm font-medium text-white truncate flex-1">
              {event.title}
            </h4>
          </div>
          <p className="text-xs text-gray-400 mt-1">
            {event.time}
          </p>
          {event.durationHours >= 1.5 && (
            <p className="text-xs text-gray-400 mt-1 line-clamp-2">
              {event.description}
            </p>
          )}
          {event.priority === 'critical' && (
            <div className="mt-2 flex items-center gap-1.5">
              <AlertTriangle className="w-3.5 h-3.5 text-red-400" />
              <span className="text-xs font-medium text-red-400">Critical</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

const Calendar = () => {
  const [selectedEvent, setSelectedEvent] = useState(null);
  const calendarRef = React.useRef(null);

  // Helper function to get next weekday date
  const getNextWeekday = (date) => {
    const nextDate = new Date(date);
    nextDate.setDate(date.getDate() + 1);
    while (nextDate.getDay() === 0 || nextDate.getDay() === 6) {
      nextDate.setDate(nextDate.getDate() + 1);
    }
    return nextDate;
  };

  // Helper function to format date to ISO string
  const formatDate = (date, time) => {
    const [hours, minutes] = time.split(':');
    const newDate = new Date(date);
    newDate.setHours(parseInt(hours), parseInt(minutes), 0);
    return newDate.toISOString();
  };

  // Generate events for the month
  const generateEvents = () => {
    let currentDate = new Date('2025-02-01');
    const endDate = new Date('2025-02-28');
    const events = [];
    let id = 1;

    while (currentDate <= endDate) {
      // Skip weekends
      if (currentDate.getDay() === 0 || currentDate.getDay() === 6) {
        currentDate.setDate(currentDate.getDate() + 1);
        continue;
      }

      // Daily morning events
      events.push({
        id: id++,
        title: "Pre-Rounds Preparation",
        start: formatDate(currentDate, '06:00'),
        end: formatDate(currentDate, '07:00'),
        time: '6:00 AM',
        description: "Review overnight cases and prepare for morning rounds",
        category: "icu",
        priority: "standard",
        location: "ICU Wing A",
        duration: 60,
        team: [
          { name: "Dr. Sarah Chen", role: "Critical Care Lead", available: true },
          { name: "Dr. Michael Patel", role: "Resident", available: true }
        ],
        relatedItems: [
          { type: 'document', title: 'Night Shift Report', description: 'Updated vitals and medication changes' }
        ]
      });

      // Daily ICU rounds
      events.push({
        id: id++,
        title: "ICU Morning Rounds",
        start: formatDate(currentDate, '08:00'),
        end: formatDate(currentDate, '10:00'),
        time: '8:00 AM',
        description: "Daily multidisciplinary ICU rounds with critical care team",
        category: "icu",
        priority: "critical",
        location: "ICU Wing A",
        duration: 120,
        team: [
          { name: "Dr. Sarah Chen", role: "Critical Care Lead", available: true },
          { name: "Dr. Michael Patel", role: "Resident", available: true },
          { name: "Dr. Emily Wong", role: "Respiratory Specialist", available: true },
          { name: "Jennifer Smith", role: "Head Nurse", available: true }
        ],
        relatedItems: [
          { type: 'alert', title: 'Ventilator Adjustment Required', description: 'Patient in Bed 3 needs settings review' },
          { type: 'document', title: 'Morning Labs', description: 'Latest test results and overnight changes' }
        ]
      });

      // Weekly tumor board (Tuesdays)
      if (currentDate.getDay() === 2) {
        events.push({
          id: 46,
          title: "Abdominal Cases MDC",
          start: formatDate(currentDate, '10:00'),
          end: formatDate(currentDate, '11:30'),
          time: '10:00 AM',
          description: "Multidisciplinary review of complex abdominal oncology cases",
          category: "oncology",
          priority: "urgent",
          location: "Conference Room 2B",
          duration: 90,
          team: [
            { name: "Dr. Lisa Anderson", role: "Medical Oncology", available: true },
            { name: "Dr. David Kim", role: "Radiation Oncology", available: true },
            { name: "Dr. Rachel Green", role: "Pathology", available: true }
          ],
          patients: [
            {
              id: 5,
              name: "Steve Jobs",
              condition: "Stage 4 Pancreatic Cancer (Neuroendocrine Tumor) - Metastatic disease with liver involvement. Previous Whipple procedure with subsequent progression.",
              status: "Under Treatment - Current therapy includes targeted molecular therapy and symptom management."
            },
            {
              id: 6,
              name: "Mallikarjun Udoshi",
              condition: "Stage 4 Colon Cancer - Metastatic adenocarcinoma with liver and peritoneal involvement. KRAS mutation positive.",
              status: "Under Treatment - Receiving FOLFOX chemotherapy with good response on recent imaging."
            }
          ],
          related_items: [
            { type: 'document', title: 'Recent Imaging', description: 'Latest CT and PET scan results' },
            { type: 'document', title: 'Treatment Protocols', description: 'Current treatment plans and response assessments' }
          ]
        });
      }

      // Neurology Rounds (Monday, Wednesday, Friday)
      if ([1, 3, 5].includes(currentDate.getDay())) {
        events.push({
          id: id++,
          title: "Neurology Department Rounds",
          start: formatDate(currentDate, '09:00'),
          end: formatDate(currentDate, '10:30'),
          time: '9:00 AM',
          description: "Comprehensive neurology rounds with focus on complex neurological cases and new admissions",
          category: "neurology",
          priority: "standard",
          location: "Neurology Wing B",
          duration: 90,
          team: [
            { name: "Dr. Oliver Sacks", role: "Chief Neurologist", available: true },
            { name: "Dr. Maya Patel", role: "Resident Neurologist", available: true },
            { name: "Dr. John Chen", role: "Stroke Specialist", available: true }
          ],
          patients: [
            {
              id: 7,
              name: "Robert Johnson",
              condition: "Post-stroke Recovery - Right-sided hemiparesis with improving speech function",
              status: "Under Treatment - Intensive physical therapy and speech rehabilitation"
            },
            {
              id: 8,
              name: "Maria Garcia",
              condition: "Early-onset Alzheimer's Disease - Showing signs of mild cognitive impairment",
              status: "Under Evaluation - Currently in clinical trial for new therapeutic approach"
            }
          ],
          related_items: [
            { type: 'document', title: 'Brain MRI Results', description: 'Latest imaging studies and progression analysis' },
            { type: 'document', title: 'Cognitive Assessment', description: 'Weekly cognitive function test results' }
          ]
        });
      }

      // Multidisciplinary Care Conference (Thursday)
      if (currentDate.getDay() === 4) {
        events.push({
          id: id++,
          title: "Complex Care Conference",
          start: formatDate(currentDate, '14:00'),
          end: formatDate(currentDate, '15:30'),
          time: '2:00 PM',
          description: "Multidisciplinary team meeting to discuss complex cases requiring coordinated care",
          category: "multidisciplinary",
          priority: "urgent",
          location: "Main Conference Center",
          duration: 90,
          team: [
            { name: "Dr. Sarah Chen", role: "Critical Care Lead", available: true },
            { name: "Dr. James Wilson", role: "Lead Surgeon", available: true },
            { name: "Dr. Oliver Sacks", role: "Chief Neurologist", available: true },
            { name: "Dr. Lisa Anderson", role: "Medical Oncology", available: true }
          ],
          patients: [
            {
              id: 9,
              name: "David Miller",
              condition: "Multiple System Trauma - Post-MVA with TBI, multiple fractures, and internal injuries",
              status: "Critical but Stable - Coordinated care between Neurology, Surgery, and Critical Care"
            }
          ],
          related_items: [
            { type: 'document', title: 'Case Summary', description: 'Comprehensive overview of patient condition and treatment plans' },
            { type: 'alert', title: 'Critical Updates', description: 'Recent changes in patient status requiring immediate attention' }
          ]
        });
      }

      // Daily surgical planning
      events.push({
        id: id++,
        title: "Surgical Planning Meeting",
        start: formatDate(currentDate, '07:00'),
        end: formatDate(currentDate, '08:00'),
        time: '7:00 AM',
        description: "Pre-operative planning for today's scheduled surgeries",
        category: "surgical",
        priority: "urgent",
        location: "OR Conference Room",
        duration: 60,
        team: [
          { name: "Dr. James Wilson", role: "Lead Surgeon", available: true },
          { name: "Dr. Maria Garcia", role: "Assistant Surgeon", available: true },
          { name: "Dr. Tom Brown", role: "Anesthesiology", available: true }
        ],
        relatedItems: [
          { type: 'document', title: 'Surgery Schedule', description: "Today's OR schedule and case details" }
        ]
      });

      currentDate.setDate(currentDate.getDate() + 1);
    }

    return events;
  };

  const events = generateEvents();
  console.log('Generated events:', events.find(e => e.id === 46));

  const handleEventClick = (clickInfo) => {
    console.log('Clicked event ID:', clickInfo.event.id);
    console.log('All events:', events);
    const event = events.find(e => e.id === parseInt(clickInfo.event.id, 10));
    console.log('Found event:', event);
    console.log('Event patients:', event?.patients);
    if (event) {
      console.log('Setting selected event with patients:', event.patients);
      setSelectedEvent({
        ...event,
        relatedItems: event.related_items || event.relatedItems || [],
        patients: event.patients || []
      });
    }
  };

  const handleDateClick = (arg) => {
    // Handle date clicks for potential new event creation
    console.log('Date clicked:', arg.dateStr);
  };

  const handleEventDrop = (dropInfo) => {
    // Handle event drag and drop
    console.log('Event dropped:', dropInfo.event.title);
  };

  const renderEventContent = (eventInfo) => {
    const event = events.find(e => e.id === parseInt(eventInfo.event.id, 10));
    if (!event) return null;

    const categoryColors = {
      'icu': 'red',
      'surgical': 'purple',
      'oncology': 'blue',
      'neurology': 'teal',
      'admin': 'gray',
      'multidisciplinary': 'green'
    };

    const categoryIcons = {
      'icu': Activity,
      'surgical': Heart,
      'oncology': Microscope,
      'neurology': Brain,
      'admin': Users,
      'multidisciplinary': Stethoscope
    };

    const Icon = categoryIcons[event.category];
    const color = categoryColors[event.category];

    return (
      <div className={`
        flex items-start gap-2 p-1 rounded-lg
        bg-${color}-900/50 border border-${color}-500/50
        hover:bg-${color}-900/70
      `}>
        {Icon && <Icon className={`w-4 h-4 text-${color}-400`} />}
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <span className={`w-2 h-2 rounded-full bg-${color}-400`} />
            <h4 className="text-sm font-medium text-white truncate">
              {eventInfo.event.title}
            </h4>
          </div>
          {event.priority === 'critical' && (
            <div className="mt-1 flex items-center gap-1.5">
              <AlertTriangle className="w-3.5 h-3.5 text-red-400" />
              <span className="text-xs font-medium text-red-400">Critical</span>
            </div>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="bg-gray-900 rounded-lg overflow-hidden border border-gray-700">
      <FullCalendar
        ref={calendarRef}
        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
        initialView="timeGridWeek"
        headerToolbar={{
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        }}
        events={events}
        eventContent={renderEventContent}
        eventClick={handleEventClick}
        dateClick={handleDateClick}
        eventDrop={handleEventDrop}
        editable={true}
        droppable={true}
        slotMinTime="05:00:00"
        slotMaxTime="22:00:00"
        allDaySlot={false}
        height="800px"
        slotDuration="00:30:00"
        nowIndicator={true}
        dayMaxEvents={true}
        navLinks={true}
        weekends={true}
        selectable={true}
        selectMirror={true}
        dayHeaderFormat={{ weekday: 'short', day: 'numeric' }}
        slotLabelFormat={{
          hour: 'numeric',
          minute: '2-digit',
          meridiem: 'short'
        }}
      />
      
      {selectedEvent && (
        <EventModal 
          event={selectedEvent}
          onClose={() => setSelectedEvent(null)}
        />
      )}
    </div>
  );
};

export default Calendar;
