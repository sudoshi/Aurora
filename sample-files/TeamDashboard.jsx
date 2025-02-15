import React, { useState, useEffect } from 'react';
import { Users, Calendar, Clock, MessageSquare, Video, AlertCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const TeamDashboard = ({ caseId }) => {
  const [teamData, setTeamData] = useState({
    members: [],
    schedules: [],
    activeDiscussions: [],
    upcomingSessions: []
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchTeamData();
    initializeRealTimeUpdates();
  }, [caseId]);

  const fetchTeamData = async () => {
    try {
      const response = await fetch(`/api/cases/${caseId}/team-dashboard`);
      const data = await response.json();
      setTeamData(data);
    } catch (error) {
      console.error('Error fetching team data:', error);
    } finally {
      setLoading(false);
    }
  };

  const initializeRealTimeUpdates = () => {
    window.Echo.private(`team.${caseId}`)
      .listen('TeamMemberStatusUpdated', (e) => {
        updateMemberStatus(e.memberId, e.status);
      })
      .listen('NewDiscussionMessage', (e) => {
        updateDiscussions(e.message);
      })
      .listen('SessionScheduled', (e) => {
        updateSessions(e.session);
      });
  };

  const updateMemberStatus = (memberId, status) => {
    setTeamData(prev => ({
      ...prev,
      members: prev.members.map(member =>
        member.id === memberId ? { ...member, status } : member
      )
    }));
  };

  const updateDiscussions = (message) => {
    setTeamData(prev => ({
      ...prev,
      activeDiscussions: [message, ...prev.activeDiscussions]
    }));
  };

  const updateSessions = (session) => {
    setTeamData(prev => ({
      ...prev,
      upcomingSessions: [...prev.upcomingSessions, session]
    }));
  };

  const TeamMemberList = () => (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Users className="h-5 w-5 text-blue-500" />
          Team Members
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {teamData.members.map((member) => (
            <div
              key={member.id}
              className="flex items-center justify-between rounded-lg border p-3"
            >
              <div className="flex items-center gap-3">
                <div className="relative">
                  <img
                    src={member.avatar}
                    alt={member.name}
                    className="h-10 w-10 rounded-full"
                  />
                  <span
                    className={`absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white ${
                      member.status === 'online'
                        ? 'bg-green-500'
                        : member.status === 'busy'
                        ? 'bg-red-500'
                        : 'bg-gray-500'
                    }`}
                  />
                </div>
                <div>
                  <p className="font-medium">{member.name}</p>
                  <p className="text-sm text-gray-500">{member.role}</p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button className="rounded-lg border p-2 hover:bg-gray-50">
                  <MessageSquare className="h-4 w-4 text-gray-500" />
                </button>
                <button className="rounded-lg border p-2 hover:bg-gray-50">
                  <Video className="h-4 w-4 text-gray-500" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );

  const UpcomingSessions = () => (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Calendar className="h-5 w-5 text-green-500" />
          Upcoming Sessions
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {teamData.upcomingSessions.map((session) => (
            <div
              key={session.id}
              className="flex items-center justify-between rounded-lg border p-3"
            >
              <div>
                <p className="font-medium">{session.title}</p>
                <div className="mt-1 flex items-center gap-2 text-sm text-gray-500">
                  <Clock className="h-4 w-4" />
                  <span>
                    {new Date(session.scheduled_start).toLocaleString()}
                  </span>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <span className="flex -space-x-2">
                  {session.participants.slice(0, 3).map((participant) => (
                    <img
                      key={participant.id}
                      src={participant.avatar}
                      alt={participant.name}
                      className="h-8 w-8 rounded-full border-2 border-white"
                      title={participant.name}
                    />
                  ))}
                  {session.participants.length > 3 && (
                    <div className="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-100 text-sm">
                      +{session.participants.length - 3}
                    </div>
                  )}
                </span>
                <button className="rounded-lg bg-blue-500 px-3 py-1 text-white hover:bg-blue-600">
                  Join
                </button>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );

  const ActiveDiscussions = () => (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <MessageSquare className="h-5 w-5 text-purple-500" />
          Active Discussions
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {teamData.activeDiscussions.map((discussion) => (
            <div
              key={discussion.id}
              className="rounded-lg border p-3"
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <img
                    src={discussion.user.avatar}
                    alt={discussion.user.name}
                    className="h-8 w-8 rounded-full"
                  />
                  <span className="font-medium">{discussion.user.name}</span>
                </div>
                <span className="text-sm text-gray-500">
                  {formatTimestamp(discussion.created_at)}
                </span>
              </div>
              <p className="mt-2 text-gray-600">{discussion.content}</p>
              {discussion.attachments && discussion.attachments.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-2">
                  {discussion.attachments.map((file) => (
                    <a
                      key={file.id}
                      href={file.url}
                      className="flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-1 text-sm text-gray-600 hover:bg-gray-200"
                    >
                      <div className="h-4 w-4">{getFileIcon(file.type)}</div>
                      {file.name}
                    </a>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );

  const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return date.toLocaleDateString();
  };

  const getFileIcon = (fileType) => {
    // Return appropriate icon based on file type
    return <div className="h-4 w-4 text-gray-500" />;
  };

  if (loading) {
    return <div>Loading team dashboard...</div>;
  }

  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
      <TeamMemberList />
      <UpcomingSessions />
      <div className="md:col-span-2">
        <ActiveDiscussions />
      </div>
    </div>
  );
};

export default TeamDashboard;