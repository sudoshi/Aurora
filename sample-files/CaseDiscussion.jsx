import React, { useState, useEffect } from 'react';
import { Paperclip, Send, Image as ImageIcon, FileText } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const CaseDiscussion = ({ caseId }) => {
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [attachments, setAttachments] = useState([]);
  const [isUploading, setIsUploading] = useState(false);

  useEffect(() => {
    // Subscribe to Laravel Echo channel for real-time updates
    window.Echo.private(`case.${caseId}`)
      .listen('NewDiscussionMessage', (e) => {
        setMessages(prev => [...prev, e.message]);
      });

    // Load initial messages
    fetchMessages();

    return () => {
      window.Echo.leave(`case.${caseId}`);
    };
  }, [caseId]);

  const fetchMessages = async () => {
    try {
      const response = await fetch(`/api/cases/${caseId}/discussions`);
      const data = await response.json();
      setMessages(data);
    } catch (error) {
      console.error('Error fetching messages:', error);
    }
  };

  const handleFileUpload = async (event) => {
    const files = Array.from(event.target.files);
    setIsUploading(true);

    try {
      const formData = new FormData();
      files.forEach(file => formData.append('files[]', file));

      const response = await fetch(`/api/cases/${caseId}/attachments`, {
        method: 'POST',
        body: formData,
      });

      const uploadedFiles = await response.json();
      setAttachments(prev => [...prev, ...uploadedFiles]);
    } catch (error) {
      console.error('Error uploading files:', error);
    } finally {
      setIsUploading(false);
    }
  };

  const sendMessage = async () => {
    if (!newMessage.trim() && attachments.length === 0) return;

    try {
      const response = await fetch(`/api/cases/${caseId}/discussions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          content: newMessage,
          attachments: attachments,
        }),
      });

      if (response.ok) {
        setNewMessage('');
        setAttachments([]);
      }
    } catch (error) {
      console.error('Error sending message:', error);
    }
  };

  const MessageItem = ({ message }) => (
    <div className="mb-4 flex gap-3">
      <img
        src={message.user.avatar}
        alt={message.user.name}
        className="h-10 w-10 rounded-full"
      />
      <div className="flex-1">
        <div className="flex items-center gap-2">
          <span className="font-medium">{message.user.name}</span>
          <span className="text-sm text-gray-500">
            {new Date(message.created_at).toLocaleString()}
          </span>
        </div>
        <div className="mt-1 rounded-lg bg-gray-50 p-3">
          <p className="text-gray-800">{message.content}</p>
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
    const isImage = file.type.startsWith('image/');
    
    return (
      <div className="relative flex items-center gap-2 rounded-lg border bg-white p-2">
        {isImage ? (
          <ImageIcon className="h-5 w-5 text-blue-500" />
        ) : (
          <FileText className="h-5 w-5 text-gray-500" />
        )}
        <span className="text-sm">{file.name}</span>
        <span className="text-xs text-gray-500">({(file.size / 1024).toFixed(1)} KB)</span>
      </div>
    );
  };

  return (
    <div className="flex h-full flex-col">
      <div className="flex-1 overflow-y-auto p-4">
        {messages.map((message) => (
          <MessageItem key={message.id} message={message} />
        ))}
      </div>

      {isUploading && (
        <Alert className="mx-4 mb-2">
          <AlertDescription>Uploading files...</AlertDescription>
        </Alert>
      )}

      <div className="border-t p-4">
        <div className="flex items-start gap-4">
          <div className="flex-1">
            <textarea
              value={newMessage}
              onChange={(e) => setNewMessage(e.target.value)}
              placeholder="Type your message..."
              className="w-full rounded-lg border p-2 focus:border-blue-500 focus:outline-none"
              rows={3}
            />
            {attachments.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2">
                {attachments.map((file) => (
                  <AttachmentPreview key={file.id} file={file} />
                ))}
              </div>
            )}
          </div>
          <div className="flex flex-col gap-2">
            <label className="cursor-pointer rounded-lg border p-2 hover:bg-gray-50">
              <input
                type="file"
                multiple
                onChange={handleFileUpload}
                className="hidden"
              />
              <Paperclip className="h-5 w-5 text-gray-500" />
            </label>
            <button
              onClick={sendMessage}
              className="rounded-lg bg-blue-500 p-2 text-white hover:bg-blue-600"
            >
              <Send className="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CaseDiscussion;