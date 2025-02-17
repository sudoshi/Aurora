import React, { useState, useEffect } from 'react';
import { Paperclip, Send, Image as ImageIcon, FileText } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

const CaseDiscussion = ({ caseId }) => {
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [attachments, setAttachments] = useState([]);
  const [isUploading, setIsUploading] = useState(false);
  const [error, setError] = useState(null);
  const { user } = useAuth();

  useEffect(() => {
    let channel;
    
    // Load initial messages
    fetchMessages();

    // Set up real-time updates if Echo is available
    if (window.Echo) {
      try {
        channel = window.Echo.private(`case.${caseId}`);
        channel.listen('NewDiscussionMessage', (e) => {
          setMessages(prev => [...prev, e.message]);
        });
      } catch (error) {
        console.error('Error setting up real-time updates:', error);
        setError('Real-time updates unavailable. Please refresh for new messages.');
      }
    }

    return () => {
      if (channel) {
        try {
          channel.stopListening('NewDiscussionMessage');
          window.Echo.leave(`case.${caseId}`);
        } catch (error) {
          console.error('Error cleaning up Echo channel:', error);
        }
      }
    };
  }, [caseId]);

  const fetchMessages = async () => {
    try {
      const response = await fetch(`/api/cases/${caseId}/discussions`, {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch messages');
      }

      const data = await response.json();
      setMessages(data);
    } catch (error) {
      console.error('Error fetching messages:', error);
      setError('Failed to load messages. Please try again.');
    }
  };

  const handleFileUpload = async (event) => {
    const files = Array.from(event.target.files);
    setIsUploading(true);
    setError(null);

    try {
      const formData = new FormData();
      files.forEach(file => formData.append('files[]', file));

      const response = await fetch(`/api/cases/${caseId}/attachments`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: formData,
      });

      if (!response.ok) {
        throw new Error('Failed to upload files');
      }

      const uploadedFiles = await response.json();
      setAttachments(prev => [...prev, ...uploadedFiles]);
    } catch (error) {
      console.error('Error uploading files:', error);
      setError('Failed to upload files. Please try again.');
    } finally {
      setIsUploading(false);
    }
  };

  const sendMessage = async () => {
    if (!newMessage.trim() && attachments.length === 0) return;
    setError(null);

    try {
      const response = await fetch(`/api/cases/${caseId}/discussions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          content: newMessage,
          attachments: attachments,
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to send message');
      }

      setNewMessage('');
      setAttachments([]);
    } catch (error) {
      console.error('Error sending message:', error);
      setError('Failed to send message. Please try again.');
    }
  };

  const MessageItem = ({ message }) => (
    <div className="mb-4 flex gap-3">
      <div className="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center">
        <span className="text-sm font-medium text-gray-100">
          {message.user?.name?.split(' ').map(n => n[0]).join('') || 'U'}
        </span>
      </div>
      <div className="flex-1">
        <div className="flex items-center gap-2">
          <span className="font-medium text-gray-100">{message.user?.name || 'Unknown User'}</span>
          <span className="text-sm text-gray-400">
            {new Date(message.created_at).toLocaleString()}
          </span>
        </div>
        <div className="mt-1 rounded-lg bg-gray-700 p-3">
          <p className="text-gray-100">{message.content}</p>
          {message.attachments && message.attachments.length > 0 && (
            <div className="mt-2 flex flex-wrap gap-2">
              {message.attachments.map((file) => (
                <AttachmentPreview key={file.id} file={file} />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const AttachmentPreview = ({ file }) => {
    const isImage = file.type?.startsWith('image/');
    
    return (
      <div className="relative flex items-center gap-2 rounded-lg border border-gray-600 bg-gray-700 p-2">
        {isImage ? (
          <ImageIcon className="h-5 w-5 text-blue-400" style={{ textAnchor: 'middle' }} />
        ) : (
          <FileText className="h-5 w-5 text-gray-400" style={{ textAnchor: 'middle' }} />
        )}
        <span className="text-sm text-gray-200">{file.name}</span>
        <span className="text-xs text-gray-400">({(file.size / 1024).toFixed(1)} KB)</span>
      </div>
    );
  };

  return (
    <div className="flex h-full flex-col bg-gray-800 min-w-0">
      <div className="flex-1 overflow-y-auto p-4 min-w-0">
        {error && (
          <div className="mb-4 p-2 bg-red-900/50 border border-red-700 text-red-200 rounded">
            {error}
          </div>
        )}
        {messages.map((message) => (
          <MessageItem key={message.id} message={message} />
        ))}
      </div>

      {isUploading && (
        <div className="mx-4 mb-2 p-2 bg-blue-900/50 border border-blue-700 text-blue-200 rounded">
          Uploading files...
        </div>
      )}

      <div className="border-t border-gray-700 p-4 flex-none">
        <div className="flex items-start gap-4 min-w-0">
          <div className="flex-1 min-w-0">
            <textarea
              value={newMessage}
              onChange={(e) => setNewMessage(e.target.value)}
              placeholder="Type your message..."
              className="w-full rounded-lg border border-gray-600 bg-gray-700 p-2 text-gray-100 placeholder-gray-400 focus:border-blue-500 focus:outline-none"
              rows={3}
            />
            {attachments.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2 min-w-0">
                {attachments.map((file) => (
                  <AttachmentPreview key={file.id} file={file} />
                ))}
              </div>
            )}
          </div>
          <div className="flex flex-col gap-2 flex-none">
            <label className="cursor-pointer rounded-lg border border-gray-600 bg-gray-700 p-2 hover:bg-gray-600">
              <input
                type="file"
                multiple
                onChange={handleFileUpload}
                className="hidden"
              />
              <Paperclip className="h-5 w-5 text-gray-300" style={{ textAnchor: 'middle' }} />
            </label>
            <button
              onClick={sendMessage}
              className="rounded-lg bg-blue-600 p-2 text-white hover:bg-blue-700"
            >
              <Send className="h-5 w-5" style={{ textAnchor: 'middle' }} />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CaseDiscussion;
