import React, { useState } from 'react';
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
          id: id++,
          title: "Tumor Board: Thoracic Cases",
          start: formatDate(currentDate, '10:30'),
          end: formatDate(currentDate, '12:00'),
          description: "Review of complex thoracic oncology cases with multidisciplinary team",
          category: "oncology",
          priority: "urgent",
          location: "Conference Room 2B",
          duration: 90,
          team: [
            { name: "Dr. Robert Martinez", role: "Thoracic Surgery", available: true },
            { name: "Dr. Lisa Anderson", role: "Medical Oncology", available: true },
            { name: "Dr. David Kim", role: "Radiation Oncology", available: true },
            { name: "Dr. Rachel Green", role: "Pathology", available: false }
          ],
          relatedItems: [
            { type: 'document', title: 'Imaging Results', description: 'Recent CT scans and PET studies' },
            { type: 'note', title: 'Previous MDT Notes', description: 'Discussion points from last meeting' }
          ]
        });
      }

      // Daily surgical planning
      events.push({
        id: id++,
        title: "Surgical Planning Meeting",
        start: formatDate(currentDate, '07:00'),
        end: formatDate(currentDate, '08:00'),
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

      // Bi-weekly transplant board (every other Wednesday)
      if (currentDate.getDay() === 3 && Math.floor(currentDate.getDate() / 7) % 2 === 0) {
        events.push({
          id: id++,
          title: "Transplant Evaluation Board",
          start: formatDate(currentDate, '09:00'),
          end: formatDate(currentDate, '11:00'),
          description: "Evaluation of potential liver transplant candidates",
          category: "surgical",
          priority: "standard",
          location: "Transplant Center",
          duration: 120,
          team: [
            { name: "Dr. James Wilson", role: "Transplant Surgery", available: true },
            { name: "Dr. Maria Garcia", role: "Hepatology", available: true },
            { name: "Dr. Tom Brown", role: "Anesthesiology", available: true },
            { name: "Dr. Susan Lee", role: "Psychiatry", available: true }
          ],
          relatedItems: [
            { type: 'document', title: 'Candidate Profiles', description: '3 new evaluations pending' },
            { type: 'alert', title: 'UNOS Status Update', description: 'Status 1A case requiring review' }
          ]
        });
      }

      // Weekly oncology planning (Thursdays)
      if (currentDate.getDay() === 4) {
        events.push({
          id: id++,
          title: "Oncology Care Planning",
          start: formatDate(currentDate, '13:00'),
          end: formatDate(currentDate, '15:00'),
          description: "Multidisciplinary review of complex oncology cases",
          category: "oncology",
          priority: "standard",
          location: "Oncology Department",
          duration: 120,
          team: [
            { name: "Dr. Rebecca Chen", role: "Medical Oncology", available: true },
            { name: "Dr. Mark Wilson", role: "Radiation Oncology", available: true },
            { name: "Dr. Laura Taylor", role: "Surgical Oncology", available: true },
            { name: "Dr. David Park", role: "Pathology", available: true }
          ],
          relatedItems: [
            { type: 'document', title: 'Treatment Protocols', description: 'Updated clinical trial options' },
            { type: 'note', title: 'Patient Responses', description: 'Current treatment efficacy data' }
          ]
        });
      }

      // Weekly neurosurgery conference (Wednesdays)
      if (currentDate.getDay() === 3) {
        events.push({
          id: id++,
          title: "Neurosurgery Conference",
          start: formatDate(currentDate, '15:30'),
          end: formatDate(currentDate, '17:00'),
          description: "Review of complex neurosurgical cases and treatment planning",
          category: "neurology",
          priority: "urgent",
          location: "Neuroscience Center",
          duration: 90,
          team: [
            { name: "Dr. Michael Chang", role: "Neurosurgery", available: true },
            { name: "Dr. Emma Roberts", role: "Neuroradiology", available: true },
            { name: "Dr. James Lee", role: "Neurology", available: true }
          ],
          relatedItems: [
            { type: 'document', title: 'MRI Studies', description: 'Recent imaging analysis' },
            { type: 'document', title: 'Surgical Approaches', description: 'Proposed intervention plans' }
          ]
        });
      }

      // Weekly department meeting (Mondays)
      if (currentDate.getDay() === 1) {
        events.push({
          id: id++,
          title: "Department Staff Meeting",
          start: formatDate(currentDate, '12:00'),
          end: formatDate(currentDate, '13:00'),
          description: "Monthly department updates and coordination meeting",
          category: "admin",
          priority: "standard",
          location: "Main Conference Room",
          duration: 60,
          team: [
            { name: "Dr. William Harris", role: "Department Head", available: true },
            { name: "Dr. Jennifer Lee", role: "Chief Resident", available: true },
            { name: "Sarah Johnson", role: "Department Coordinator", available: true }
          ],
          relatedItems: [
            { type: 'document', title: 'Monthly Metrics', description: 'Department performance review' },
            { type: 'document', title: 'Staffing Updates', description: 'New hires and schedule changes' }
          ]
        });
      }

      // Daily evening handoff
      events.push({
        id: id++,
        title: "Evening Handoff Meeting",
        start: formatDate(currentDate, '17:00'),
        end: formatDate(currentDate, '18:00'),
        description: "Shift change handoff and patient status updates",
        category: "multidisciplinary",
        priority: "critical",
        location: "Central Nursing Station",
        duration: 60,
        team: [
          { name: "Dr. Lisa Wong", role: "Day Shift Lead", available: true },
          { name: "Dr. Michael Chen", role: "Night Shift Lead", available: true },
          { name: "Sarah Miller", role: "Charge Nurse", available: true }
        ],
        relatedItems: [
          { type: 'document', title: 'Shift Report', description: 'Comprehensive handoff documentation' },
          { type: 'alert', title: 'Critical Cases', description: 'High-priority patients requiring attention' }
        ]
      });

      // Daily ED huddle
      events.push({
        id: id++,
        title: "Emergency Department Huddle",
        start: formatDate(currentDate, '19:00'),
        end: formatDate(currentDate, '20:00'),
        description: "Evening ED status review and resource allocation",
        category: "icu",
        priority: "urgent",
        location: "ED Conference Room",
        duration: 60,
        team: [
          { name: "Dr. Alex Johnson", role: "ED Lead", available: true },
          { name: "Dr. Karen White", role: "Trauma Surgery", available: true },
          { name: "Dr. Hassan Ahmed", role: "Internal Medicine", available: false }
        ],
        relatedItems: [
          { type: 'alert', title: 'Department Status', description: 'Current ED capacity and pending cases' },
          { type: 'note', title: 'Resource Updates', description: 'Available beds and specialist coverage' }
        ]
      });
      // Monthly grand rounds (first Friday)
      if (currentDate.getDay() === 5 && currentDate.getDate() <= 7) {
        events.push({
          id: id++,
          title: "Grand Rounds",
          start: formatDate(currentDate, '14:00'),
          end: formatDate(currentDate, '16:00'),
          category: "surgical",
          priority: "standard",
          location: "Main Auditorium",
          description: "Monthly hospital-wide grand rounds featuring guest speakers and case presentations",
          team: [
            { name: "Dr. William Harris", role: "Department Head", available: true },
            { name: "Dr. Sarah Chen", role: "Chief of Surgery", available: true }
          ]
        });
      }

      // Bi-weekly case review (every other Monday)
      if (currentDate.getDay() === 1 && Math.floor(currentDate.getDate() / 7) % 2 === 1) {
        events.push({
          id: id++,
          title: "Multidisciplinary Case Review",
          start: formatDate(currentDate, '14:00'),
          end: formatDate(currentDate, '16:00'),
          category: "multidisciplinary",
          priority: "standard",
          location: "Conference Room A",
          description: "Comprehensive review of complex cases requiring multi-specialty input",
          team: [
            { name: "Dr. Lisa Wong", role: "Internal Medicine", available: true },
            { name: "Dr. Michael Chen", role: "Surgery", available: true },
            { name: "Dr. Emily Taylor", role: "Oncology", available: true }
          ]
        });
      }

      currentDate.setDate(currentDate.getDate() + 1);
    }

    return events.map(event => ({
      ...event,
      description: event.description || "Regular scheduled meeting",
      category: event.category || (() => {
        if (event.title.includes("ICU") || event.title.includes("Emergency")) return "icu";
        if (event.title.includes("Surgical") || event.title.includes("Transplant")) return "surgical";
        if (event.title.includes("Oncology") || event.title.includes("Tumor")) return "oncology";
        if (event.title.includes("Neuro")) return "neurology";
        if (event.title.includes("Department") || event.title.includes("Staff")) return "admin";
        return "multidisciplinary";
      })(),
      priority: event.priority || "standard",
      location: event.location || "Hospital Wing A",
      team: event.team || [
        { name: "Dr. John Doe", role: "Lead Physician", available: true },
        { name: "Dr. Jane Smith", role: "Specialist", available: true }
      ]
    }));
  };

  const events = generateEvents();

  const handleEventClick = (clickInfo) => {
    const event = events.find(e => e.id === parseInt(clickInfo.event.id));
    if (event) {
      setSelectedEvent(event);
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
    const event = events.find(e => e.id === parseInt(eventInfo.event.id));
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
        theme={{
          background: '#111827',
          textColor: '#fff'
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
