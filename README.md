#  FeedNews.me
A powerful dashboard tool to keep track of your favorite news sites, daily tasks, reminders, and so much more. FeedNews.me, previously called "It's my Homepage" brings you a site that you can set as startpage of your browser, or use Electron to run it as an app. I personally use it all day, with my news sources like Neowin, iDownloadblog and many more next to my Youtube subscriptions, todo-list and weather updates all in one place.

# Use online version
In case you wish to use the functionality, or just have a look around but you don't want to go through all the installation hussle. Visit [Feednews.me](http://feednews.me) and create a account.

# Functionality
- Read and manage RSS Feeds
    - Assign colors to different sources
    - Assign FontAwesome icons to each source (optional)
    - Search items
    - Pin items to read later
    - Add pages to view later
    - Share a page with friends
    - Youtube support (theme switches to dark mode when watching video)

- Screensaver functionality (image at the bottom of this readme)
    - Set [yourdomain]/screensaver/ as screensaver to any OS and display random images with your news
    - Option to combine images from services like Unsplash with your own image gallery services
    - Show current weather condition and temperature

- Todo list
    - Need to keep track of things? Simply use the searchbar to add items, or use the todo page to manage your list. Quick and easy

- Weather radar
    - It currently displays just weather of the Netherlands, but  will add support for more countries later
    - Show the current weather conditions of your location (set up in config)

- Image share functionality
    - Share an image with a shortlink from mobile or from clipboard (soon available)
    - View and remove shared images
    - Share previous shared images

- Notes
    - Add one or more notes with autosaving. It's saved while you type.


# Wishlist
- Plugins
    - Some options might not be interesting for the public. For that a plugin way of adding icons without git's interference would be a nice addition, and adds flexibility for those who like to use this tool.
    - Plugin share aka Play Store?
- Themes
    - Design always has a different taste with each and everyone. So adding theme support would be nice.
- Documentation


# Installation
Use the .env file in the backend directory to create your personal config file. You need to set your database config and enter you openWeatherMap API key. This is a required step for now.

After saving your config, make sure to run the following commands from your terminal. Currently, all commands need to run on both dev and production env. This will probably change in the future.

- fill .env file with server settings
- ddev ssh
- cd backend
- composer install
- php bin/console d:m:m

If you want to change styling, also run:
- TODO

# Mobile support
FeedNews.me is fully responsive. On Android (Chrome) or iOS (Safari), save the page to your home screen for a fullscreen experience. This is especially handy for features like the shopping-list–style to-do list.

# Contributing
Ideas, feedback, and contributions are welcome. If you’d like to help, fork the repository and submit a pull request. New ideas and improvements are always appreciated.

# Docker
Run `ddev start` command from a terminal to launch this app in a docker environment

[![StackShare](https://img.shields.io/badge/tech-stack-0690fa.svg?style=flat)](https://stackshare.io/webstylecenter/homepage)
