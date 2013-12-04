OpenVBX-Plugin-Combined-Call-Transfer-beta
==========================================
Call transfer between OpenVBX users with optional incoming call log/recording feature. 



<h1>Install</h1>
- Copy the 'OpenVBX-Plugin-Combined-Call-Transfer' directory into the 'plugins' directory of your OpenVBX setup.
- Copy the 'OpenVBX/controllers/requests.php' file into 'OpenVBX/controllers' directory.
- Copy the 'OpenVBX/models/vbx_recorded_call.php' file into 'OpenVBX/models' directory.

<h2>How this works</h2>
- Set the 'Join Call' applet into a call flow.
- When this flow will receive an inbound call, it will try to add an agent into a conference room. 
- After that, it will add the caller into the same room. And then both parties can talk each other in that room.
- If the agent will need to add another agent, he can go to: 'Call Transfer' from the left menu and join another online agent into that call.
- The conference will continue until the caller hangup himself.

<h2>'Join Call' applet fields: </h2>
- 'Dial a user or group' - You can add a single user ort a group here. The system will call the users one by one.
- 'Waiting Speech' - Add some texts/audio, so it will say/play to the caller after several seconds. i.e. 'Please wait. One of our agent will join shortly. Thank you'.
- 'Call Recording' - This is optional.
- 'Join Music' - This will play when a single user will be in the conference room.
- 'If nobody answers...' - Applet if no one receives the call.
