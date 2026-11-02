# Course Registration System (CRS)

A web-based **Course Registration System (CRS)** developed as part of the Software Engineering course at the **Islamic University of Madinah**, First Semester 2024–2025.

The system automates course scheduling and registration, helping administrators manage courses, sections, classrooms, and faculty assignments while allowing students and faculty members to manage their academic activities.

## Table of Contents

1. [Abstract](#abstract)
2. [Problem Statement](#problem-statement)
3. [Objectives](#objectives)
4. [Main Features](#main-features)
5. [User Roles](#user-roles)
6. [System Requirements](#system-requirements)
7. [Technologies and Development Environment](#technologies-and-development-environment)
8. [System Architecture](#system-architecture)
9. [Database Design](#database-design)
10. [Scheduling Logic](#scheduling-logic)
11. [System Diagrams](#system-diagrams)
12. [Application Interfaces](#application-interfaces)
    - [Login Interface](#1-login-interface)
    - [Admin Interface](#2-admin-interface)
    - [Doctor Interface](#3-doctor-interface)
    - [Student Interface](#4-student-interface)
13. [Error Handling](#error-handling)
14. [Challenges and Solutions](#challenges-and-solutions)
15. [Testing and Simulation Environment](#testing-and-simulation-environment)
16. [Limitations and Future Enhancements](#limitations-and-future-enhancements)


## Abstract

The Course Registration System is designed to automate academic course scheduling and registration processes. Traditional manual scheduling requires administrators to create course sections, assign classrooms and faculty members, and ensure that students are registered in suitable courses without scheduling conflicts.

The proposed system provides a centralized web-based platform with three user roles: Admin, Doctor (Faculty Member), and Student.

Administrators can manage courses, sections, classrooms, students, and faculty members. Faculty members can submit course preferences, view their timetables, and access their assigned sections and enrolled students. Students can view their academic plans, register for eligible courses, and add, drop, or change sections.

The system aims to improve scheduling efficiency, reduce manual errors, and simplify academic registration.

## Problem Statement

Traditional course scheduling is a manual process that requires administrators to coordinate courses, faculty members, classrooms, and student registrations.

This process can lead to several problems:

- **Time Conflicts:** Overlapping schedules between students, faculty members, and classrooms.
- **Class Size Management:** Difficulty ensuring that section capacity matches student enrollment.
- **Manual Workload:** Creating and managing schedules requires considerable administrative effort.
- **Registration Errors:** Students may attempt to register for courses without meeting prerequisite or academic-level requirements.

The Course Registration System addresses these challenges through automated scheduling and centralized registration management.

## Objectives

The main objectives of this project are:

- Automate the creation and management of course sections.
- Prevent scheduling conflicts between courses, faculty members, and classrooms.
- Assign suitable classrooms based on room type and capacity.
- Assign faculty members based on availability and course preferences.
- Register students according to academic eligibility and prerequisites.
- Provide separate dashboards for administrators, faculty members, and students.
- Simplify the process of adding, dropping, and changing course sections.

## Main Features

### 1. Automated Course Scheduling
- Automatically create sections based on the number of eligible students.
- Assign available classrooms to sections.
- Allocate suitable time slots.
- Prevent overlapping schedules.
- Consider classroom type (Theory or Lab) and capacity.

### 2. Course and Section Management
- View available courses and their information.
- Create and manage course sections.
- Assign faculty members, rooms, and time slots.
- View section details and enrolled students.

### 3. Faculty Management
- Allow faculty members to submit course preferences.
- Display assigned sections.
- View teaching timetables.
- View enrolled students in each assigned section.

### 4. Student Registration
- View academic plans and timetables.
- Browse available sections.
- Register for eligible courses.
- Add or drop sections.
- Change registered sections.
- Register for courses above the student's current academic level when eligibility requirements are satisfied.

### 5. User Authentication
- Authenticate users using their usernames or IDs and passwords.
- Identify user roles.
- Redirect users to their corresponding dashboards.

## User Roles

### Admin

The administrator is responsible for managing the overall scheduling and registration process.

- View dashboard statistics.
- Manage students.
- Manage faculty members.
- Manage courses.
- Manage classrooms.
- Create and manage sections.
- Assign rooms, time slots, and faculty members.
- View enrolled students in sections.

### Doctor (Faculty Member)

Faculty members can manage their teaching preferences and view their assigned academic information.

- View the faculty dashboard.
- Select preferred courses.
- View personal timetable.
- View assigned sections.
- View lists of enrolled students.

### Student

Students can manage their course registration and view their academic schedules.

- View the student dashboard.
- View academic plans.
- View personal timetables.
- View registered sections.
- Browse available sections.
- Add or drop sections.
- Change registered sections.
- Register for eligible courses above their current academic level.

## System Requirements

### Functional Requirements

- Authenticate users and identify their roles.
- Redirect users to the appropriate dashboard.
- Allow administrators to create sections based on eligible students.
- Assign rooms and time slots while preventing conflicts.
- Assign faculty members based on availability and preferences.
- Allow faculty members to submit course preferences.
- Allow faculty members to view their assigned sections and enrolled students.
- Allow students to register for courses based on prerequisites and academic level.
- Allow students to add, drop, and change sections.
- Display student plans, timetables, and registered sections.
- Prevent enrollment in ineligible courses.

### Non-Functional Requirements

- **Usability:** Provide a user-friendly interface for all user roles.
- **Performance:** Support efficient scheduling and registration operations.
- **Security:** Protect student, faculty, and administrative information.
- **Maintainability:** Keep the system organized and easy to modify.
- **Reliability:** Prevent invalid registrations and scheduling conflicts.

## Technologies and Development Environment

| Component | Technology |
|---|---|
| Backend | PHP |
| Frontend | HTML, CSS, JavaScript |
| Database | MySQL |
| Local Server | XAMPP |
| Code Editor | Visual Studio Code |
| Browser Testing | Google Chrome |

## System Architecture

The system follows a web-based architecture consisting of three main components:

1. **Frontend:** HTML, CSS, and JavaScript provide the user interfaces and interactive functionality.
2. **Backend:** PHP handles authentication, registration operations, scheduling logic, and communication with the database.
3. **Database:** MySQL stores users, courses, sections, classrooms, faculty information, and student registrations.

The application uses role-based authentication to direct users to their corresponding dashboards.

## Database Design

The database contains the following main tables:

| Table | Description |
|---|---|
| `admin` | Stores administrator information. |
| `completed_courses` | Tracks courses completed by students. |
| `course` | Stores available course information. |
| `doctor` | Stores faculty member information. |
| `doctor_courses` | Links faculty members with their preferred courses. |
| `room` | Stores classroom information, type, and capacity. |
| `sections` | Stores created sections, assigned rooms, faculty, and time slots. |
| `student` | Stores student information and academic levels. |
| `student_course_registration` | Records student course and section registrations. |
| `user` | Stores login credentials and user roles. |

### Main Database Relationships

- The `user` table manages authentication for Admin, Doctor, and Student accounts.
- A course can have multiple sections.
- Faculty members can be associated with courses through `doctor_courses`.
- Faculty members are assigned to sections.
- Each section is assigned a classroom.
- Students register for sections through `student_course_registration`.
- Completed courses are tracked through `completed_courses` to support prerequisite checks.

## Scheduling Logic

One of the main challenges of the system is automatically assigning classrooms and time slots without conflicts.

### 1. Section Creation
- Identify students eligible for each course.
- Consider academic levels and completed prerequisites.
- Determine the number of sections required.

### 2. Room Assignment
- Select classrooms compatible with the section type (Theory or Lab).
- Check classroom capacity.
- Ensure that the selected room is available.

### 3. Time Slot Assignment
- Check available days and time slots.
- Prevent overlapping room schedules.
- Prevent conflicts between sections for the same academic level.
- Distribute the required teaching hours throughout the week.

### 4. Faculty Assignment
- Consider faculty course preferences.
- Check faculty availability.
- Prevent overlapping teaching schedules.

### Conflict Detection

The scheduling algorithm uses two main tracking structures:

- `roomOccupancy`: Tracks time slots already assigned to each classroom.
- `levelOccupancy`: Tracks time slots occupied by each academic level.

Before assigning a section, the system checks these structures to identify available time slots and avoid conflicts.

## System Diagrams

The project includes three diagrams for modeling the system.

### Entity-Relationship (ER) Diagram

Illustrates the database entities, attributes, and relationships between tables.

![ER Diagram](ER%20diagram.png)

### Class Diagram

Represents the main system classes and their relationships.

![Class Diagram](Class%20Diagram.png)

### Use Case Diagram

Illustrates the interactions between the Admin, Doctor, and Student roles and the system's functionalities.

![Use Case Diagram](Use%20case%20diagram.png)

## Application Interfaces

The system provides separate interfaces for administrators, faculty members, and students.

### 1. Login Interface

Users enter their username or ID and password. The system validates their credentials and redirects them according to their role.

![Login Page](Login%20page.png)

### 2. Admin Interface

The admin interface provides an overview of the system and access to its management functions.

**Admin Dashboard**

![Admin Interface](Admin%20Interface.png)

**Course List**

![Course List](Course%20List.png)

**Course List and Section Creation**

![Course List and Section Creation](Course%20List%20%26%20Section%20Creation.png)

**Student List**

![Student List](Student%20List.png)

**Doctor List**

![Doctor List](Doctor%20List.png)

**Room List**

![Room List](Room%20List.png)

**Sections List**

![Sections List](Sections%20List.png)

**Section Details**

![Section Details](Section%20details.png)

**Assign Doctors**

![Assign Doctors](Assign%20Doctors.png)

**Assign Sections to Students**

![Assign Sections to Students](Assign%20Sections%20to%20Students.png)

**Assign Times and Rooms**

![Assign Times and Rooms](Assign%20Times%20%26%20Rooms.png)

### 3. Doctor Interface

Faculty members can view their preferences, teaching timetable, assigned sections, and enrolled students.

**Doctor Dashboard**

![Doctor Interface](Doctor%20Interface.png)

**Doctor Timetable**

![Timetable for Doctor](Timetable%20for%20Doctor.png)

**Assigned Sections List**

![Sections List Assigned for a Doctor](Section%20Lists%20assigned%20for%20a%20doctor.png)

**Section Details and Enrolled Students**

![Section Details for Doctor](Section%20Details%20for%20doctor.png)

### 4. Student Interface

Students can view their academic information and manage their course registrations.

**Student Dashboard**

![Student Dashboard](Student%20Dashboard.png)

**Student Academic Plan**

![Student Plan](Student%20Plan.png)

**Student Timetable**

![Student Timetable](Student%20Timetable.png)

**Registered Sections**

![Registered Section for Student](Registered%20section%20for%20Student.png)

**Available Sections for Changing Registered Courses**

![Available Sections for Changing Registered Courses](Available%20Sections%20for%20Changing%20Registered%20Courses%20for%20the%20student.png)

**Available Sections to Drop Above Student Level**

![Available Sections to Drop Above Student Level](Available%20Sections%20to%20Drop%20Above%20Student%20level.png)

**Available Sections to Register Above Student Level**

![Available Sections to Register Above Student Level](Available%20Sections%20to%20Register%20Above%20Student%20level.png)

## Error Handling

The system includes checks to prevent invalid or redundant operations.

One implemented example is the section creation process:

- Before inserting sections for a course, the system checks whether sections already exist.
- If sections are found, the system skips creating duplicate entries.

The scheduling process also checks room availability and academic-level occupancy before assigning time slots.

## Challenges and Solutions

### Challenge: Scheduling Conflicts

Assigning rooms and time slots while satisfying course requirements was one of the main challenges.

**Solution:**

A conflict-resolution algorithm was developed using:

- Room compatibility checks for Theory and Lab sections.
- Room capacity checks.
- The `roomOccupancy` structure to track occupied classroom time slots.
- The `levelOccupancy` structure to track occupied academic-level time slots.
- Availability checks across different days and time slots.

This approach helps assign suitable classrooms and schedules while avoiding overlapping sessions.

### Challenge: Student Eligibility

Students must meet academic-level and prerequisite requirements before registering for courses.

**Solution:**

The system checks student academic levels and completed courses before allowing registration.

### Challenge: Duplicate Section Creation

Repeated section generation could create duplicate records.

**Solution:**

The system checks the existing sections table before inserting new sections.

## Testing and Simulation Environment

The application was tested locally using **XAMPP**, which provided the local server environment and MySQL database.

Google Chrome was used to access and test the web application.

Development and implementation were carried out using Visual Studio Code.

## Limitations and Future Enhancements

### Current Limitations

- Limited flexibility for complex faculty scheduling preferences.
- Administrators may require training to use all system functionalities.
- Additional database and performance optimization may be needed for larger institutions.
- More advanced scheduling customization could improve the system.

### Future Enhancements

1. **Advanced Faculty Preferences:** Support more detailed teaching preferences and timetable constraints.
2. **Data Analytics:** Provide enrollment statistics and scheduling insights for administrators.
3. **Scalability:** Improve database optimization and system performance for larger institutions.
4. **Mobile Application:** Develop a mobile-friendly interface or dedicated mobile application.
5. **Notifications:** Send email or SMS notifications about schedule changes, available sections, and registration deadlines.
6. **Improved Customization:** Support institution-specific scheduling rules and academic requirements.





