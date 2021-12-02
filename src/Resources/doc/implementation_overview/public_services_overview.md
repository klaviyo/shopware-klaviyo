# Public services

## Overview

In general those classes are public and can be used outside the plugin.
They are responsible for execution of the high level feature use cases.
In other worlds they are an entry points to the plugin functionality


## Available services

### EventsTracker

Responsible for tracking of the Klaviyo events using Klaviyo API on the low level. 
Tracking is designed to be synchronous. 
This service is responsible to check that event should be tracked in plugin settings.

### AsyncEventsTracker

Responsible for starting an asynchronous tracking of the Klaviyo events.
For now asynchronous tracking is implemented only for historical events

### TrackingCatalogFeedProvider

Responsible for providing a catalog feed in the format of the Klaviyo catalog feed

### VirtualProxyJobScheduler

Responsible for scheduling of the synchronization jobs