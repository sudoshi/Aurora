import React, { useState, useEffect } from 'react';
import { Calendar as CalendarIcon, Clock, Users, VideoIcon } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Calendar } from '@/components/ui/calendar';

const SessionScheduler = ({ caseId, team }) => {
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [selectedTime, setSelectedTime] = useState('');
  const [duration, setDuration] = useState(30);
  const [selectedMembers, setSelectedMembers] = useState([]);
  const [availabilityData, setAvailabilityData] = useState({});

  useEffect(() => {
    if (selectedDate) {
      fetchTeamAvailability(selectedDate);
    }
  }, [selectedDate]);

  const fetchTeamAvailability = async (date) => {
    try {
      const response = await fetch(`/api/team/availability/${date.toISOString()}`);
      const data = await response.json();
      setAvailabilityData(data);
    } catch (error) {
      console.error('Error fetching availability:', error);
    }
  };

  const handleScheduleSession = async () => {
    const sessionData = {
      case_id: caseId,
      scheduled_start: new Date(`${selectedDate.toDateString()} ${selectedTime}`),
      duration,
      participants: selectedMembers,
    };

    try {
      const response = await fetch('/api/sessions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sessionData),
      });

      if (response.ok) {
        // Handle success
        onScheduled();
      }
    } catch (error) {
      console.error('Error scheduling session:', error);
    }
  };

  return (
    <Dialog>
      <DialogTrigger asChild>
        <button className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
          <CalendarIcon className="h-4 w-4" />
          Schedule Session
        </button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Schedule Case Discussion</DialogTitle>
        </DialogHeader>
        <div className="grid gap-4 py-4">
          <div className="grid gap-2">
            <label className="text-sm font-medium">Date</label>
            <Calendar
              mode="single"
              selected={selectedDate}
              onSelect={setSelectedDate}
              className="rounded-md border"
              disabled={(date) => date < new Date()}
            />
          </div>

          <div className="grid gap-2">
            <label className="text-sm font-medium">Time</label>
            <div className="grid grid-cols-4 gap-2">
              {generateTimeSlots().map((time) => (
                <button
                  key={time}
                  onClick={() => setSelectedTime(time)}
                  className={`rounded-md border p-2 text-sm ${
                    selectedTime === time
                      ? 'border-blue-500 bg-blue-50'
                      : 'hover:bg-gray-50'
                  } ${
                    isTimeSlotAvailable(time, availabilityData)
                      ? ''
                      : 'cursor-not-allowed opacity-50'
                  }`}
                  disabled={!isTimeSlotAvailable(time, availabilityData)}
                >
                  {time}
                </button>
              ))}
            </div>
          </div>

          <div className="grid gap-2">
            <label className="text-sm font-medium">Duration</label>
            <select
              value={duration}
              onChange={(e) => setDuration(Number(e.target.value))}
              className="rounded-md border p-2"
            >
              <option value={30}>30 minutes</option>
              <option value={60}>1 hour</option>
              <option value={90}>1.5 hours</option>
              <option value={120}>2 hours</option>
            </select>
          </div>

          <div className="grid gap-2">
            <label className="text-sm font-medium">Team Members</label>
            <div className="max-h-48 overflow-y-auto rounded-md border p-2">
              {team.members.map((member) => (
                <label
                  key={member.id}
                  className="flex items-center gap-2 p-2 hover:bg-gray-50"
                >
                  <input
                    type="checkbox"
                    checked={selectedMembers.includes(member.id)}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedMembers([...selectedMembers, member.id]);
                      } else {
                        setSelectedMembers(
                          selectedMembers.filter((id) => id !== member.id)
                        );
                      }
                    }}
                    className="rounded border-gray-300"
                  />
                  <span>{member.name}</span>
                  <span className="text-sm text-gray-500">({member.role})</span>
                </label>
              ))}
            </div>
          </div>

          <button
            onClick={handleScheduleSession}
            disabled={!selectedDate || !selectedTime || selectedMembers.length === 0}
            className="mt-4 flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <VideoIcon className="h-4 w-4" />
            Schedule Video Session
          </button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

// Helper function to generate time slots
const generateTimeSlots = () => {
  const slots = [];
  for (let hour = 8; hour < 18; hour++) {
    slots.push(`${hour.toString().padStart(2, '0')}:00`);
    slots.push(`${hour.toString().padStart(2, '0')}:30`);
  }
  return slots;
};

// Helper function to check time slot availability
const isTimeSlotAvailable = (time, availabilityData) => {
  // Implement availability logic based on your requirements
  return true; // Placeholder
};

export default SessionScheduler;