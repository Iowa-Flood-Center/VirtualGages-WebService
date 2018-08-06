# VirtualGages-WebService

IFC's web service system for providing real time date of the so called Virtual Gages

It is composed by a set of *bash*/*python*/*php* scripts and *php*/*json* pages distributed between a *backend* and a *frontend* system components as briefly described bellow. For more details regarding each of those components, please find more documentation on their respective folders.

## Backend

This component has the objective of:
- harvest the data produced by the observation gages, state and forecast models;
- post-process the harvested information to be used optimally by the *frontend* component;
- keep a historical dataset of harverest data.

## Frontend

This component is designed to provide a communication interface with other tools and users by:
- accessing the data produced by the *backend* component;
- providing visual and text-based web interfaces to such data;
- communicating via e-mail to certain people when events of interest are detected.
