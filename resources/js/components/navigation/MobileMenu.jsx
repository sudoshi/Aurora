import React, { Fragment } from 'react';
import { Link } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import { X, Menu as MenuIcon } from 'lucide-react';

const MobileMenu = ({ isOpen, setIsOpen, navigation, userNavigation }) => {
  return (
    <>
      {/* Mobile menu button */}
      <div className="md:hidden">
        <button
          type="button"
          className="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
          onClick={() => setIsOpen(true)}
        >
          <span className="sr-only">Open main menu</span>
          <MenuIcon className="block h-6 w-6" aria-hidden="true" />
        </button>
      </div>

      {/* Mobile menu panel */}
      <Transition.Root show={isOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50 md:hidden" onClose={setIsOpen}>
          <Transition.Child
            as={Fragment}
            enter="transition-opacity ease-linear duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="transition-opacity ease-linear duration-300"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-gray-900 bg-opacity-75" />
          </Transition.Child>

          <div className="fixed inset-0 z-40 flex">
            <Transition.Child
              as={Fragment}
              enter="transition ease-in-out duration-300 transform"
              enterFrom="-translate-x-full"
              enterTo="translate-x-0"
              leave="transition ease-in-out duration-300 transform"
              leaveFrom="translate-x-0"
              leaveTo="-translate-x-full"
            >
              <Dialog.Panel className="relative flex w-full max-w-xs flex-1 flex-col bg-gray-800">
                <div className="absolute right-0 top-0 -mr-12 pt-4">
                  <button
                    type="button"
                    className="flex h-10 w-10 items-center justify-center focus:outline-none"
                    onClick={() => setIsOpen(false)}
                  >
                    <X className="h-6 w-6 text-white" aria-hidden="true" />
                  </button>
                </div>

                {/* Mobile menu content */}
                <div className="flex-1 overflow-y-auto pb-4">
                  {/* Logo */}
                  <div className="flex items-center px-6 pt-6 pb-4 border-b border-gray-700">
                    <Link to="/" className="flex items-center space-x-2" onClick={() => setIsOpen(false)}>
                      <span className="text-xl font-bold text-white">Aurora</span>
                    </Link>
                  </div>

                  {/* Navigation */}
                  <nav className="px-4 pt-4 space-y-2">
                    {navigation.map((item) => (
                      <div key={item.name} className="py-2">
                        {item.items ? (
                          <>
                            <div className="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-md">
                              <item.icon className="h-5 w-5 mr-3" aria-hidden="true" />
                              {item.name}
                            </div>
                            <div className="mt-2 pl-11 space-y-1">
                              {item.items.map((subItem) => (
                                <Link
                                  key={subItem.name}
                                  to={subItem.href}
                                  className="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-md hover:bg-gray-700 hover:text-white"
                                  onClick={() => setIsOpen(false)}
                                >
                                  <subItem.icon className="h-5 w-5 mr-3" aria-hidden="true" />
                                  {subItem.name}
                                </Link>
                              ))}
                            </div>
                          </>
                        ) : (
                          <Link
                            to={item.href}
                            className="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-md hover:bg-gray-700 hover:text-white"
                            onClick={() => setIsOpen(false)}
                          >
                            <item.icon className="h-5 w-5 mr-3" aria-hidden="true" />
                            {item.name}
                          </Link>
                        )}
                      </div>
                    ))}
                  </nav>
                </div>

                {/* User navigation */}
                <div className="border-t border-gray-700 p-4">
                  <div className="flex items-center mb-4">
                    <div className="ml-3">
                      <div className="text-sm font-medium text-gray-400">Quick Links</div>
                    </div>
                  </div>
                  <div className="space-y-1">
                    {userNavigation.map((item) => (
                      <Link
                        key={item.name}
                        to={item.href}
                        className="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-md hover:bg-gray-700 hover:text-white"
                        onClick={() => setIsOpen(false)}
                      >
                        <item.icon className="h-5 w-5 mr-3" aria-hidden="true" />
                        {item.name}
                      </Link>
                    ))}
                  </div>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </Dialog>
      </Transition.Root>
    </>
  );
};

export default MobileMenu;
