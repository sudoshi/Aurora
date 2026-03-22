import { Bell } from "lucide-react";
import { NotificationSettings } from "../components/NotificationSettings";

export default function NotificationSettingsPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <div className="flex items-center gap-3 mb-1">
          <div className="flex items-center justify-center w-10 h-10 rounded-lg bg-[#2DD4BF]/10">
            <Bell size={20} className="text-[#2DD4BF]" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-[#E8ECF4]">
              Notification Preferences
            </h1>
            <p className="text-sm text-[#7A8298]">
              Configure how and when you receive notifications
            </p>
          </div>
        </div>
      </div>

      {/* Content */}
      <NotificationSettings />
    </div>
  );
}
