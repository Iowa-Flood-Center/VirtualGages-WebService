# Frontend - Code - Site

The codes in this directory are expected to be accessed and executed through a web server such as *Apache*. 

## summary.php

The main interface between the final user and the data. It provides a *CSV*-like output in which the number and types of columns are variable to attend different user requests.

### Usage

Just access the page through a web browser and provide the optional arguments as needed in the URL by GET. 

The optional arguments are described as follows.

- **forecast\_id**
  - Description: The model id for the forecast
  - In absence: Just provide past data
  - Format: String
  - Example: *http://.../summary.php?forecast\_id=fc254ifc01norain*
- **only\_forecast**
  - Description: Determines whether if past data will be provided or not
  - In absence: Assumes "no" (show past data) 
  - Format: String. Expects only "yes" | "no". 
  - Example:  *http://.../vsummary.php?forecast\_id=fc254ifc01norain&only\_forecast=yes*
- **ifis\_id**
  - Description: Limits the output for a single virtual gages with given *ifis id*
  - In absence: Provides outputs for all locations
  - Format: Integer
  - Example: *http://.../summary.php?ifis\_id=2005*
- **min\_datetime**
  - Description: Limits the output for past data for a given minimum value
  - In absence: Takes as limit 10 days in the past from current time 
  - Format: String with format "*m\_d\_Y*"
  - Example: *http://.../summary.php?min\_datetime=07\_22\_2018*
- **max\_datetime**
  - Description: Limits the output for future data for a given maximum value
  - In absence: Takes as limit 10 days in the future from current time
  - Format: String with format "*m\_d\_Y*"
  - Example: *http://.../summary.php?max\_datetime=07\_22\_2018*
- **min\_timestamp**
  - Description: Limits the output for past data for a given minimum value
  - In absence: Takes as limit 10 days in the past from current time
  - Format: Integer
  - Example: *http://.../summary.php?min\_timestamp=1520452800*
- **max\_timestamp**
  - Description: Determines limiting timestamps for output data
  - In absence: Takes as limit 10 days in the future from current time
  - Format: Integer
  - Example: *http://.../summary.php?max\_timestamp=1520452800*
- **show\_me**
  - Description: Determines if provided output is given with 'glued' or 'raw' data 
  - In absence: Assumes "the\_glue"
  - Format: String. Expects only "the\_truth" | "the\_glue"
  - Example: *http://.../summary.php?show\_me=the\_truth*

## thresholds.php

A simple web service for providing access to the values of the thresholds associated to the available virtual gages in *JSON* format.

### Usage

The user can request the data for all points (what would return a list of JSON objects) or only for one point (which returns a single JSON object if found, or an empty list otherwise).

- **all sites**
  - URL call: *http://.../thresholds.php*
- **only one site**
  - URL call: *http://.../thresholds.php?ifis_id=<INTEGER\>*

## graph.html

Over simplistic page that just plots the content of a call to the *summary.php* service using *Google Annotation Charts*.

### Usage

It requires an HTTP GET request containing the arguments **forecast\_id** and **ifis\_id**.

Example: *http://.../graph.html?forecast\_id=fc254ifc01qpf&ifis_id=2014*
