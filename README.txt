Intro

This block provides a Welcome Area for Moodle courses that is unique by
instructor instead of by course.  This is useful for displaying information
that is repeated by a teacher across a number of courses.


Displaying the Welcome Area

By default this welcome area is displayed in the block itself. However the
block supports displaying the welcome area in a larger block on the course
page.

// Not yet supported in 2.3:
//To display the welcome area at the top-center of the course add this line to
//moodle/course/view.php:
//    require_once($CFG->dirroot . '/blocks/welcomearea/lib.php');
//And add this line to moodle/course/format/*/format.php:
//    welcomearea_display();
//after:
//    echo '<td id="middle-column">';
//    print_container_start();
//In-block display can be turned off in the block settings.


Editing the Welcome Area

Edits to the welcome area are made by instructors by following the link in the
block on any course page they are listed as an instructor for. Once on the edit
page, changes are made using the editor. Instructors can also view
their welcome area history by following the link on the edit page. Once on the
history page, instructors can revert to a previous welcome area.

Administrators and Managers will also be able to go to a course and edit the
welcome area shown for the course.


Default Welcome Area

Administrators can define a default welcome area that is used on courses that do
not have an instructor, as well as in place of an instructors welcome area that
hasn't be defined.

The link to edit the default welcome area will display for admins on any
instance of the block. The interface is the same as the one used by instructors,
and has a history as well.


Display Settings

When multiple instructors are assigned to a course, by the default the block
will display the welcome area of the instructor that was assigned to the course
first. This can be overridden by an administrator an a per course basis. This is
accomplished by following the display settings link in the block on the course.

Options for display are:

    Auto (default operation)
    *Any Instructor Assigned to Course*
    Default Welcome Area
    No Display

The no display option will disable display of the welcome area for the course.
