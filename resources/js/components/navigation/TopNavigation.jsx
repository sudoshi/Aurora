import React, { Fragment } from 'react';
import { Link } from 'react-router-dom';
import { Menu, Transition } from '@headlessui/react';
import { 
  Video, 
  Share2, 
  Layout, 
  MessageSquare, 
  FileText, 
  Bell, 
  Stethoscope,
  TestTube2, 
  Pill, 
  AlertTriangle,
  Users, 
  Calendar, 
  FileCheck,
  ClipboardList,
  Settings,
  Shield,
  Bell as BellIcon,
  User,
  LogOut,
  Search,
  ChevronDown,
  Activity,
  BarChart2,
  Clipboard,
  Heart,
  UserCog,
  FileBarChart
} from 'lucide-react';

const navigation = [
  {
    name: 'Dashboard',
    href: '/',
    icon: Layout,
    color: 'gray'
  },
  {
    name: 'Clinical',
    icon: Heart,
    color: 'blue',
    items: [
      { name: 'Patient Records', href: '/patients', icon: Users, shortcut: '⌘P' },
      { name: 'Lab Results', href: '/lab-results', icon: TestTube2, shortcut: '⌘L' },
      { name: 'Medications', href: '/medications', icon: Pill, shortcut: '⌘M' },
      { name: 'Treatment Plans', href: '/treatments', icon: Clipboard, shortcut: '⌘T' },
      { name: 'Emergency Protocols', href: '/emergency', icon: AlertTriangle, shortcut: '⌘E' },
    ],
    badge: '3 urgent'
  },
  {
    name: 'Communication',
    icon: MessageSquare,
    color: 'green',
    items: [
      { name: 'Video Calls', href: '/video-calls', icon: Video, shortcut: '⌘V' },
      { name: 'Messages', href: '/messages', icon: MessageSquare, badge: '5 new' },
      { name: 'Team Chat', href: '/team-chat', icon: Users },
      { name: 'Share Files', href: '/share', icon: Share2 },
    ],
  },
  {
    name: 'Administrative',
    icon: UserCog,
    color: 'purple',
    items: [
      { name: 'Documentation', href: '/docs', icon: FileText },
      { name: 'Scheduling', href: '/schedule', icon: Calendar },
      { name: 'Task Manager', href: '/tasks', icon: ClipboardList, badge: '2 due' },
      { name: 'Compliance', href: '/compliance', icon: Shield },
    ],
  },
  {
    name: 'Analytics',
    icon: FileBarChart,
    color: 'orange',
    items: [
      { name: 'Reports', href: '/reports', icon: FileText },
      { name: 'Statistics', href: '/statistics', icon: BarChart2 },
      { name: 'Performance', href: '/performance', icon: Activity },
      { name: 'Audit Logs', href: '/audit-logs', icon: ClipboardList },
    ],
  },
];

const userNavigation = [
  { name: 'Profile Settings', href: '/profile', icon: User },
  { name: 'Help & Support', href: '/help', icon: MessageSquare },
  { name: 'Sign Out', href: '/logout', icon: LogOut },
];

const NavMenuItem = ({ item }) => {
  if (item.items) {
    return (
      <Menu as="div" className="relative">
        {({ open }) => (
          <>
            <Menu.Button 
              className={`
                btn btn-ghost gap-2 h-16 px-4 min-w-[120px]
                ${open ? 'bg-gray-700' : ''}
                ${item.color ? `text-${item.color}-400 hover:text-${item.color}-300` : ''}
              `}
            >
              <div className="flex items-center gap-2">
                <item.icon className="w-5 h-5" aria-hidden="true" />
                <span>{item.name}</span>
                {item.badge && (
                  <span className="badge badge-sm badge-error">{item.badge}</span>
                )}
                <ChevronDown 
                  className={`w-4 h-4 transition-transform duration-200 ${open ? 'rotate-180' : ''}`} 
                  aria-hidden="true" 
                />
              </div>
            </Menu.Button>
            <Transition
              as={Fragment}
              enter="transition ease-out duration-100"
              enterFrom="transform opacity-0 scale-95"
              enterTo="transform opacity-100 scale-100"
              leave="transition ease-in duration-75"
              leaveFrom="transform opacity-100 scale-100"
              leaveTo="transform opacity-0 scale-95"
            >
              <Menu.Items className="absolute left-0 mt-0 w-72 origin-top-left rounded-lg bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div className="p-2 space-y-1">
                  {item.items.map((subItem) => (
                    <Menu.Item key={subItem.name}>
                      {({ active }) => (
                        <Link
                          to={subItem.href}
                          className={`
                            group flex items-center justify-between px-4 py-3 text-sm rounded-md
                            ${active ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'}
                          `}
                        >
                          <div className="flex items-center gap-3">
                            <subItem.icon 
                              className={`w-5 h-5 ${item.color ? `text-${item.color}-400 group-hover:text-${item.color}-300` : ''}`} 
                              aria-hidden="true" 
                            />
                            <span>{subItem.name}</span>
                          </div>
                          <div className="flex items-center gap-3">
                            {subItem.badge && (
                              <span className="badge badge-sm badge-primary">{subItem.badge}</span>
                            )}
                            {subItem.shortcut && (
                              <kbd className="hidden lg:inline-block px-2 py-1 text-xs font-semibold text-gray-400 bg-gray-700 rounded">
                                {subItem.shortcut}
                              </kbd>
                            )}
                          </div>
                        </Link>
                      )}
                    </Menu.Item>
                  ))}
                </div>
              </Menu.Items>
            </Transition>
          </>
        )}
      </Menu>
    );
  }

  return (
    <div className="relative">
      <Link
        to={item.href}
        className={`
          btn btn-ghost h-16 px-4 min-w-[120px] flex items-center
          ${item.color ? `text-${item.color}-400 hover:text-${item.color}-300` : ''}
        `}
      >
        <div className="flex items-center gap-2">
          <item.icon className="w-5 h-5" aria-hidden="true" />
          <span>{item.name}</span>
        </div>
      </Link>
    </div>
  );
};

const TopNavigation = () => {
  return (
    <nav className="fixed top-0 left-0 right-0 z-50 bg-gray-800 border-b border-gray-700 shadow-lg">
      <div className="max-w-[1920px] mx-auto px-4">
        <div className="flex h-16 items-center justify-between">
          {/* Logo */}
          <Link to="/" className="flex items-center justify-center gap-3 h-16 px-4 flex-shrink-0">
            <Heart className="w-5 h-5 text-blue-500" />
            <span className="text-xl font-bold text-white">Aurora</span>
          </Link>

          {/* Main Navigation */}
          <div className="flex items-center">
            {navigation.map((item) => (
              <NavMenuItem key={item.name} item={item} />
            ))}
          </div>

          {/* Right Section */}
          <div className="flex items-center gap-4">
            {/* Search */}
            <div className="form-control">
              <input
                type="search"
                className="input input-bordered w-64 h-16"
                placeholder="Search patients, records..."
              />
            </div>

            {/* Notifications */}
            <button className="btn btn-ghost btn-circle h-16 w-16">
              <div className="indicator">
                <BellIcon className="h-6 w-6 text-orange-400" aria-hidden="true" />
                <span className="indicator-item badge badge-error badge-xs" />
              </div>
            </button>

            {/* Profile dropdown */}
            <Menu as="div" className="relative">
              {({ open }) => (
                <>
                  <Menu.Button className={`
                    btn btn-ghost gap-2 px-4 h-16 min-w-[120px]
                    ${open ? 'bg-gray-700' : ''}
                  `}>
                    <div className="flex items-center gap-2">
                      <User className="w-5 h-5 text-purple-400" aria-hidden="true" />
                      <span>Dr. Udoshi</span>
                      <ChevronDown 
                        className={`w-4 h-4 transition-transform duration-200 ${open ? 'rotate-180' : ''}`} 
                        aria-hidden="true" 
                      />
                    </div>
                  </Menu.Button>
                  <Transition
                    as={Fragment}
                    enter="transition ease-out duration-100"
                    enterFrom="transform opacity-0 scale-95"
                    enterTo="transform opacity-100 scale-100"
                    leave="transition ease-in duration-75"
                    leaveFrom="transform opacity-100 scale-100"
                    leaveTo="transform opacity-0 scale-95"
                  >
                    <Menu.Items className="absolute right-0 mt-0 w-56 origin-top-right rounded-lg bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                      <div className="p-2 space-y-1">
                        {userNavigation.map((item) => (
                          <Menu.Item key={item.name}>
                            {({ active }) => (
                              <Link
                                to={item.href}
                                className={`
                                  group flex items-center px-4 py-3 text-sm rounded-md
                                  ${active ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'}
                                `}
                              >
                                <item.icon className="w-5 h-5 mr-3 text-purple-400 group-hover:text-purple-300" aria-hidden="true" />
                                {item.name}
                              </Link>
                            )}
                          </Menu.Item>
                        ))}
                      </div>
                    </Menu.Items>
                  </Transition>
                </>
              )}
            </Menu>
          </div>
        </div>
      </div>
    </nav>
  );
};

export default TopNavigation;
