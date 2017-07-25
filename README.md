# EventoImport

Evento Import is a Cron-Plugin that updates Users and ILIAS-Object-Members based on information received from a SOAP-Interface

**Minimum ILIAS Version:**
5.2.0

**Maximum ILIAS Version:**
5.2.999

**Responsible Developer:**
Stephan Winiker - stephan.winiker@hslu.ch

**Supported Languages:**
German, English

### Quick Installation Guide
1. Copy the content of this folder in <ILIAS_directory>/Customizing/global/plugins/Services/Cron/CronHook/EventoImport or clon this Github-Repo to <ILIAS_directory>/Customizing/global/plugins/Services/Cron/CronHook/

2. Access ILIAS, go to the administration menu and select "Plugins" in the menu on the right.

3. Look for the Evento Import plugin in the table, press the "Action" button and seect "Update".

4. Press the "Action" button and select "Activate" to activate the plugin.

5. Press the "Action" button and select "Refresh Languages" to update the language-files.

6. Go to the administration menu, select "General Settings" and then "Cron Jobs".

7. Look for "Evento Import " in the table and click "Edit".

8. Choose your schedule and change the rest of the settings to your needs. Don't forget to adapt the Mail-Text to something sensible.

9. Save and activate the Cron-Job.