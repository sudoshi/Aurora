import React, { useState, useEffect } from 'react';
import { Bell, AlertCircle, Clock, Check, X } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const NotificationCenter = ({ userId, caseId }) => {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [showNotifications, setShowNotifications] = useState(false);
  const [selectedNotification, setSelectedNotification] = useState(null);

  useEffect(() => {
    // Subscribe to user-specific notifications
    window.Echo.private(`user.${userId}`)
      .listen('ClinicalAlert', (e) => {
        handleNewNotification(e.notification);
      });

    // Subscribe to team notifications
    window.Echo.private(`team.${caseId}`)
      .listen('TeamNotification', (e) => {
        handleNewNotification(e.notification);
      });

    // Load existing notifications
    fetchNotifications();

    return () => {
      window.Echo.leave(`user.${userId}`);
      window.Echo.leave(`team.${caseId}`);
    };
  }, [userId, caseId]);

  const fetchNotifications = async () => {
    try {
      const response = await fetch(`/api/notifications`);
      const data = await response.json();
      setNotifications(data);
      setUnreadCount(data.filter(n => !n.read_at).length);
    } catch (error) {
      console.error('Error fetching notifications:', error);
    }
  };

  const handleNewNotification = (notification) => {
    setNotifications(prev => [notification, ...prev]);
    setUnreadCount(prev => prev + 1);

    // Show alert for high-priority notifications
    if (notification.priority === 'high' || notification.priority === 'urgent') {
      showAlert(notification);
    }
  };

  const showAlert = (notification) => {
    // Use the system's native notification if available
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.content, {
        icon: '/notification-icon.png',
        body: notification.content
      });
    }
  };

  const markAsRead = async (notificationId) => {
    try {
      await fetch(`/api/notifications/${notificationId}/read`, {
        method: 'POST'
      });

      setNotifications(prev =>
        prev.map(n =>
          n.id === notificationId
            ? { ...n, read_at: new Date().toISOString() }
            : n
        )
      );
      setUnreadCount(prev => prev - 1);
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const NotificationItem = ({ notification }) => (
    <div
      className={`mb-2 cursor-pointer rounded-lg border p-3 ${
        !notification.read_at
          ? 'border-blue-500 bg-blue-50'
          : 'border-gray-200'
      }`}
      onClick={() => setSelectedNotification(notification)}
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          {getPriorityIcon(notification.priority)}
          <span className="font-medium">{notification.type}</span>
        </div>
        <span className="text-sm text-gray-500">
          {formatTimestamp(notification.created_at)}
        </span>
      </div>
      <p className="mt-1 text-gray-600">{notification.content}</p>
    </div>
  );

  const getPriorityIcon = (priority) => {
    switch (priority) {
      case 'urgent':
      case 'emergency':
        return <AlertCircle className="h-5 w-5 text-red-500" />;
      case 'high':
        return <AlertCircle className="h-5 w-5 text-orange-500" />;
      default:
        return <Bell className="h-5 w-5 text-blue-500" />;
    }
  };

  const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // difference in seconds

    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return date.toLocaleDateString();
  };

  return (
    <>
      <div className="relative">
        <button
          onClick={() => setShowNotifications(true)}
          className="relative rounded-full p-2 hover:bg-gray-100"
        >
          <Bell className="h-6 w-6" />
          {unreadCount > 0 && (
            <span className="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white">
              {unreadCount}
            </span>
          )}
        </button>
      </div>

      <Dialog open={showNotifications} onOpenChange={setShowNotifications}>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Notifications</DialogTitle>
          </DialogHeader>
          <div className="max-h-[60vh] overflow-y-auto">
            {notifications.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-8 text-gray-500">
                <Bell className="h-12 w-12 opacity-50" />
                <p className="mt-2">No notifications yet</p>
              </div>
            ) : (
              notifications.map((notification) => (
                <NotificationItem
                  key={notification.id}
                  notification={notification}
                />
              ))
            )}
          </div>
        </DialogContent>
      </Dialog>

      {/* Detailed Notification View */}
      <Dialog
        open={!!selectedNotification}
        onOpenChange={() => setSelectedNotification(null)}
      >
        <DialogContent className="sm:max-w-[600px]">
          {selectedNotification && (
            <>
              <DialogHeader>
                <div className="flex items-center justify-between">
                  <DialogTitle className="flex items-center gap-2">
                    {getPriorityIcon(selectedNotification.priority)}
                    {selectedNotification.type}
                  </DialogTitle>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => markAsRead(selectedNotification.id)}
                      className="rounded-full p-2 hover:bg-gray-100"
                    >
                      <Check className="h-5 w-5 text-green-500" />
                    </button>
                    <button
                      onClick={() => setSelectedNotification(null)}
                      className="rounded-full p-2 hover:bg-gray-100"
                    >
                      <X className="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </DialogHeader>

              <div className="mt-4 space-y-4">
                <div>
                  <h3 className="font-medium">Details</h3>
                  <p className="mt-1 text-gray-600">
                    {selectedNotification.content}
                  </p>
                </div>

                {selectedNotification.metadata && (
                  <div>
                    <h3 className="font-medium">Related Information</h3>
                    <div className="mt-2 space-y-2">
                      {Object.entries(selectedNotification.metadata).map(([key, value]) => (
                        <div
                          key={key}
                          className="flex items-center justify-between rounded-lg border p-2"
                        >
                          <span className="text-gray-600">
                            {key.replace(/_/g, ' ').charAt(0).toUpperCase() +
                              key.slice(1).replace(/_/g, ' ')}
                          </span>
                          <span className="font-medium">{value}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                <div>
                  <h3 className="font-medium">Timestamp</h3>
                  <p className="mt-1 text-gray-600">
                    {new Date(selectedNotification.created_at).toLocaleString()}
                  </p>
                </div>

                {selectedNotification.actions && (
                  <div>
                    <h3 className="font-medium">Actions Required</h3>
                    <div className="mt-2 space-y-2">
                      {selectedNotification.actions.map((action, index) => (
                        <button
                          key={index}
                          onClick={() => handleNotificationAction(action)}
                          className="w-full rounded-lg border p-2 text-left hover:bg-gray-50"
                        >
                          {action.label}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
};

export default NotificationCenter;