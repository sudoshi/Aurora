// resources/js/components/VideoChat.jsx
import React, { useEffect, useState } from 'react';
import AgoraRTC from 'agora-rtc-sdk-ng';

const VideoChat = ({ sessionId, userId, role }) => {
  const [localVideoTrack, setLocalVideoTrack] = useState(null);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [client, setClient] = useState(null);

  useEffect(() => {
    const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
    setClient(client);

    const init = async () => {
      // Initialize Agora client
      await client.join(process.env.AGORA_APP_ID, sessionId, null, userId);
      
      // Create and publish local tracks
      const tracks = await AgoraRTC.createMicrophoneAndCameraTracks();
      await client.publish(tracks);
      setLocalVideoTrack(tracks[1]);

      // Handle remote users
      client.on('user-published', async (user, mediaType) => {
        await client.subscribe(user, mediaType);
        if (mediaType === 'video') {
          setRemoteUsers(prev => [...prev, user]);
        }
      });

      client.on('user-unpublished', (user) => {
        setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
      });
    };

    init();

    return () => {
      client?.leave();
      localVideoTrack?.close();
    };
  }, [sessionId]);

  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      {/* Local video */}
      <div className="relative h-64 rounded-lg bg-gray-900">
        <div id="local-video" className="h-full w-full" ref={el => {
          if (el && localVideoTrack) {
            localVideoTrack.play(el);
          }
        }} />
        <div className="absolute bottom-2 left-2 rounded bg-black/50 px-2 py-1 text-sm text-white">
          You
        </div>
      </div>

      {/* Remote videos */}
      {remoteUsers.map(user => (
        <div key={user.uid} className="relative h-64 rounded-lg bg-gray-900">
          <div id={`remote-video-${user.uid}`} className="h-full w-full" ref={el => {
            if (el && user.videoTrack) {
              user.videoTrack.play(el);
            }
          }} />
          <div className="absolute bottom-2 left-2 rounded bg-black/50 px-2 py-1 text-sm text-white">
            {user.uid}
          </div>
        </div>
      ))}

      {/* Controls */}
      <div className="fixed bottom-4 left-1/2 flex -translate-x-1/2 transform gap-4 rounded-full bg-gray-900 p-4">
        <button className="rounded-full bg-red-500 p-3 text-white hover:bg-red-600">
          <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <button className="rounded-full bg-gray-700 p-3 text-white hover:bg-gray-600">
          <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
          </svg>
        </button>
        <button className="rounded-full bg-gray-700 p-3 text-white hover:bg-gray-600">
          <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
        </button>
      </div>
    </div>
  );
};

export default VideoChat;