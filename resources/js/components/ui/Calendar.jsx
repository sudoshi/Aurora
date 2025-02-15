import React, { useState } from 'react';
import { ChevronLeft, ChevronRight, AlertTriangle, Activity, Stethoscope, Users, CalendarDays, Brain, Heart, Microscope } from 'lucide-react';
import EventModal from './EventModal';

const ViewButton = ({ label, isActive, onClick }) => (
  <button
    onClick={onClick}
    className={`
      px-3 py-1.5 rounded-lg text-sm font-medium transition-colors
      ${isActive 
        ? 'bg-primary text-white' 
        : 'bg-gray-800 text-gray-300 hover:bg-gray-700'}
    `}
  >
    {label}
  </button>
);

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
  const [currentDate, setCurrentDate] = useState(new Date());
  const [currentView, setCurrentView] = useState('week');
  const [selectedEvent, setSelectedEvent] = useState(null);
  
  const hours = Array.from({ length: 17 }, (_, i) => i + 5); // 5am to 10pm
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  const today = new Date();

  // Updated events with realistic timing
  const mockEvents = [
    {
      id: 1,
      day: 1,
      startHour: 6,
      durationHours: 1,
      title: "Pre-Rounds Preparation",
      time: "6:00 AM",
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
    },
    {
      id: 2,
      day: 1,
      startHour: 8,
      durationHours: 2,
      title: "ICU Morning Rounds",
      time: "8:00 AM",
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
    },
    {
      id: 3,
      day: 1,
      startHour: 10.5,
      durationHours: 1.5,
      title: "Tumor Board: Thoracic Cases",
      time: "10:30 AM",
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
    },
    {
      id: 4,
      day: 2,
      startHour: 7,
      durationHours: 1,
      title: "Surgical Planning Meeting",
      time: "7:00 AM",
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
    },
    {
      id: 5,
      day: 2,
      startHour: 9,
      durationHours: 2,
      title: "Transplant Evaluation Board",
      time: "9:00 AM",
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
    },
    {
      id: 6,
      day: 3,
      startHour: 13,
      durationHours: 2,
      title: "Oncology Care Planning",
      time: "1:00 PM",
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
    },
    {
      id: 7,
      day: 3,
      startHour: 15.5,
      durationHours: 1.5,
      title: "Neurosurgery Conference",
      time: "3:30 PM",
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
    },
    {
      id: 8,
      day: 4,
      startHour: 12,
      durationHours: 1,
      title: "Department Staff Meeting",
      time: "12:00 PM",
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
    },
    {
      id: 9,
      day: 4,
      startHour: 17,
      durationHours: 1,
      title: "Evening Handoff Meeting",
      time: "5:00 PM",
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
    },
    {
      id: 10,
      day: 5,
      startHour: 19,
      durationHours: 1,
      title: "Emergency Department Huddle",
      time: "7:00 PM",
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
    }
  ];

  const handlePrevious = () => {
    const newDate = new Date(currentDate);
    if (currentView === 'day') {
      newDate.setDate(currentDate.getDate() - 1);
    } else if (currentView === 'week') {
      newDate.setDate(currentDate.getDate() - 7);
    } else {
      newDate.setMonth(currentDate.getMonth() - 1);
    }
    setCurrentDate(newDate);
  };

  const handleNext = () => {
    const newDate = new Date(currentDate);
    if (currentView === 'day') {
      newDate.setDate(currentDate.getDate() + 1);
    } else if (currentView === 'week') {
      newDate.setDate(currentDate.getDate() + 7);
    } else {
      newDate.setMonth(currentDate.getMonth() + 1);
    }
    setCurrentDate(newDate);
  };

  const handleToday = () => {
    setCurrentDate(new Date());
  };

  const getDisplayDays = () => {
    if (currentView === 'day') {
      return [currentDate];
    }
    
    const dates = [];
    const startOfWeek = new Date(currentDate);
    startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());

    for (let i = 0; i < 7; i++) {
      const date = new Date(startOfWeek);
      date.setDate(startOfWeek.getDate() + i);
      dates.push(date);
    }
    return dates;
  };

  const isToday = (date) => {
    return date.toDateString() === today.toDateString();
  };

  return (
    <>
      <div className="bg-gray-900 rounded-lg overflow-hidden border border-gray-700 h-[800px] flex flex-col">
        {/* Calendar Header */}
        <div className="p-4 border-b border-gray-700 flex items-center justify-between flex-none">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <button 
                onClick={handlePrevious}
                className="p-2 hover:bg-gray-700 rounded-lg"
              >
                <ChevronLeft className="w-5 h-5 text-gray-400" />
              </button>
              <h2 className="text-xl font-semibold text-white">
                {currentDate.toLocaleDateString('en-US', { 
                  month: 'long',
                  year: 'numeric',
                  ...(currentView === 'day' && { day: 'numeric' })
                })}
              </h2>
              <button 
                onClick={handleNext}
                className="p-2 hover:bg-gray-700 rounded-lg"
              >
                <ChevronRight className="w-5 h-5 text-gray-400" />
              </button>
            </div>

            <div className="flex gap-2">
              <ViewButton 
                label="Day"
                isActive={currentView === 'day'}
                onClick={() => setCurrentView('day')}
              />
              <ViewButton 
                label="Week"
                isActive={currentView === 'week'}
                onClick={() => setCurrentView('week')}
              />
            </div>
          </div>

          <button 
            onClick={handleToday}
            className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-800 text-gray-300 hover:bg-gray-700"
          >
            <CalendarDays className="w-4 h-4" />
            Today
          </button>
        </div>

        {/* Calendar Grid */}
        <div className="flex-1 flex overflow-hidden">
          {/* Time Column */}
          <div className="w-20 flex-none border-r border-gray-700 bg-gray-800/50">
            <div className="h-14 border-b border-gray-700" /> {/* Header spacer */}
            <div className="relative">
              {hours.map(hour => (
                <div 
                  key={hour}
                  className="h-20 -mt-2.5 flex items-start justify-center text-sm font-medium text-gray-400"
                >
                  <span className="pt-1">
                    {hour === 0 ? '12 AM' : hour < 12 ? `${hour} AM` : hour === 12 ? '12 PM' : `${hour - 12} PM`}
                  </span>
                </div>
              ))}
            </div>
          </div>

          {/* Days Grid */}
          <div className="flex-1 overflow-auto">
            <div className="min-w-full h-full">
              {/* Days Header */}
              <div className="flex border-b border-gray-700 sticky top-0 bg-gray-900 z-20">
                {getDisplayDays().map((date, index) => (
                  <div 
                    key={index}
                    className={`
                      flex-1 h-14 flex flex-col justify-center items-center border-r border-gray-700
                      ${isToday(date) ? 'bg-primary/10' : ''}
                    `}
                    style={{ minWidth: '150px' }}
                  >
                    <span className="text-sm font-medium text-gray-400">
                      {date.toLocaleDateString('en-US', { weekday: 'short' })}
                    </span>
                    <span className={`text-lg font-semibold ${isToday(date) ? 'text-primary' : 'text-white'}`}>
                      {date.getDate()}
                    </span>
                  </div>
                ))}
              </div>

              {/* Time Grid */}
              <div className="flex relative">
                {getDisplayDays().map((date, dayIndex) => (
                  <div 
                    key={dayIndex}
                    className={`
                      flex-1 border-r border-gray-700 relative
                      ${isToday(date) ? 'bg-primary/5' : ''}
                    `}
                    style={{ minWidth: '150px' }}
                  >
                    {hours.map(hour => (
                      <div 
                        key={hour}
                        className="h-20 border-b border-gray-700/50"
                      />
                    ))}
                    {/* Events for this day */}
                    {mockEvents
                      .filter(event => event.day === dayIndex)
                      .map(event => (
                        <EventCard 
                          key={event.id} 
                          event={event}
                          onClick={setSelectedEvent}
                          isSelected={selectedEvent?.id === event.id}
                        />
                      ))}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Event Modal */}
      {selectedEvent && (
        <EventModal 
          event={selectedEvent}
          onClose={() => setSelectedEvent(null)}
        />
      )}
    </>
  );
};

export default Calendar;
