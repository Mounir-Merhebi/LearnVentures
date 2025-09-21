<img src="./readme/title1.svg"/>

<br><br>

<!-- project overview -->
<img src="./readme/title2.svg"/>

> *The Problem:*<br>
Traditional classrooms treat every student the same, but no two learners are alike. Students lose motivation because lessons feel generic, while instructors struggle to adapt materials for different learning styles.
>
> *The Solution:*<br>
Our platform transforms static lessons into *personalized learning experiences*. Instructors upload their curriculum, and AI instantly tailors examples, practice questions, and analogies to each student’s interests and hobbies without losing accuracy. Every student gets content that feels made just for them.


<br><br>

<!-- System Design -->
<img src="./readme/title3.svg"/>

### ER Diagram

<img src="./readme/er_diagram.svg"/>

### SYSTEM Architecture

<img src="./readme/demo/Architecture.png"/>
<br><br>

<!-- Project Highlights -->
<img src="./readme/title4.svg"/>

### LearnVentures Sexy Features

- ✨ *AI-Personalized Lessons*
Students don’t just read, they connect. A football fan sees math problems framed as game stats, while a gamer sees them as power-ups. Same core concept, different engagement.

- 🤖 *A PrivateChat Section between the student and Optimus*
 A section for the student to chat privately with Optimus and ask them about definitions and clarifications. also it includes voice transcribtion where the student can record a voice and the voice will be transcribed to text message before the student can send it to optimus.

- 📈 *Quiz Performance Analysis*
The AI analyzes each quiz within the chapters to provide detailed performance insights. Students don’t just get scores. they receive a personalized performance analysis and a tailored study plan that highlights strengths, pinpoints weaknesses, and recommends next steps for improvement.

- 📝 *Daily Conversation Report Automation*
Instructors receive a daily report summarizing every conversation between students and the AI chatbot Optimus. Each report is automatically generated every 24 hours, providing a clear record of student–AI interactions. This keeps instructors informed, ensures transparency in learning progress, and helps maintain accountability in how students engage with the AI.

<img src="./readme/demo/features.png"/>

<br><br>

<!-- Demo -->
<img src="./readme/title5.svg"/>

### User Screens (Web)

| Landing Page 1                     | Login                             | Register                        |
| ---------------------------------- | --------------------------------- | -------------------------------- |
| ![Landing](./readme/demo/Landing1.png) | ![Login](./readme/demo/Login.png) | ![Register](./readme/demo/register.png) |

| Landing page 2                            | User Dashboard                       |Student Profile                      |
| --------------------------------------- | ------------------------------------- | ------------------------------------- |
| ![Landing](./readme/demo/Landing2.png) | ![fsdaf](./readme/demo/Dashboard.png) | ![fsdaf](./readme/demo/profile.png) |

| Optimus studying Assistant                           | Personilized Lesssons                       |Quizz System                      |
| --------------------------------------- | ------------------------------------- | ------------------------------------- |
| ![Landing](./readme/demo/chatbot.gif) | ![fsdaf](./readme/demo/personilized.gif) | ![fsdaf](./readme/demo/quizz.gif) |

### Admin Screens (Web)

| Content managment                             | Chat daily Report                       |
| --------------------------------------- | ------------------------------------- |
| ![Landing](./readme/demo/managment.gif) | ![fsdaf](./readme/demo/report.gif) |

### Automation Workflow

| n8n workflow                             | 
| --------------------------------------- | 
| ![Landing](./readme/demo/n8n.gif) | 



<br><br>

<!-- Development & Testing -->
<img src="./readme/title6.svg"/>

### CI Workflow

| CI 1                             | CI 2                       |
| --------------------------------------- | ------------------------------------- |
| ![Landing](./readme/demo/ci1.png) | ![fsdaf](./readme/demo/ci2.png) |

### Services, Validation and Testing

| Services                            | Validation                       | Testing                        |
| --------------------------------------- | ------------------------------------- | ------------------------------------- |
| ![Landing](./readme/demo/service.png) | ![fsdaf](./readme/demo/request.png) | ![fsdaf](./readme/demo/test.png) |



<br><br>

<!-- Deployment -->
<img src="./readme/title7.svg"/>

### Development → Deployment Flow

1. **Feature Development**
   - Work begins on a new feature inside a local branch.
   - The branch is pushed to its remote equivalent on GitHub.

2. **Integration to Staging**
   - The remote feature branch is merged into the staging branch.
   - This triggers GitHub Actions.

3. **CI on Staging**
   - GitHub Actions provisions a temporary database.
   - Migrations run, automated tests execute, and the app is booted in a test environment.
   - If all checks pass, the pipeline continues.

4. **Staging Deployment**
   - GitHub Actions pushes code to the staging EC2 instance.
   - A deployment script builds Docker containers:
     - Laravel backend
     - Node services
     - Database
     - React frontend
   - Containers spin up, serving the staging environment.

5. **Production Release**
   - Once the feature is approved, staging is merged into the main branch.
   - GitHub Actions reruns the same pipeline steps, but deployment is directed to the production EC2 instance.

<br>

   <img src="./readme/demo/deployment.png"/>

<br> 

###  Linear Workflow

Below is a screenshot of our Linear board, which we used to manage tasks throughout development:

![Linear Board Screenshot](./readme/demo/linear.png)

**Workflow steps:**
- Create ticket in Linear  
- Make a branch following Linear naming standards  
- Commit changes with task IDs mentioned in commit messages  
- Push branch to remote  
- Open a pull request  
- Merge pull request once approved  

---

## Eraser Diagrams
We used [Eraser](https://app.eraser.io/) to design and maintain our architecture and database diagrams.  
Eraser was chosen because:
- **Diagram-as-code**: diagrams are represented in a simple code-like format, making them version-controllable.  
- **Ease of use**: allows quick edits and sharing without heavy tools.  
- **Collaboration**: teammates can easily view or update diagrams.  

[Eraser Public Board Link](https://app.eraser.io/workspace/3zg4gXt67Cxc4bqKBnSu)


| Swagger API 1                            | Swagger API 2                       | Swagger API 3                        |
| --------------------------------------- | ------------------------------------- | ------------------------------------- |
| ![Landing](./readme/demo/api1.png) | ![fsdaf](./readme/demo/api2.png) | ![fsdaf](./readme/demo/api3.png) |

<br><br>