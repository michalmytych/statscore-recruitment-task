# Solution for recruitment task: Football Events Application

## Architecture approach
Because I decided to focus mainly on meeting the business requirements of the project, I avoided adding complex architecture and modeling. Instead, I chose a simpler approach that is still clean and correct in my opinion.

At the same time, I’d like to explain what approaches I would use in a more developed version of this project:
	•	Event Sourcing seems like a good fit, because it naturally allows building complex projections from different types of events. I utilized model which is a simple example of event-first approach: calculating statistics as live events projection.
	•	CQRS would also work well, as it clearly separates read and write models—especially since these processes would likely be handled by different business actors. For example, data providers could handle statistics updates, while SaaS clients would focus on reading data.
	•	DDD (Domain-Driven Design) also makes sense, as it helps with clean implementation of business rules. It is also a popular standard, which makes the code easier to understand for other developers.

In this solution, however, I focused on the technical requirements instead of adding more abstraction layers. At the same time, I kept a clear separation between application code and domain processes (event handling), so the structure remains safe and maintainable.

All simplifications are explained in code comments (although in a real project I would avoid adding so many comments). I also proposed an infrastructure-level approach by introducing a message broker and meeting the real-time requirement using WebSockets.

## Key decisions & changes
* Completely removed `statistics` as a persisted image of events - instead I introduced generating them on-the-fly as a projection of events.
This eliminates a lot of problems & complications with data integrity `events state <-> statistics state`. The cost of this decision is that calculations are performed on every request but as the feature seems reads-heavy and not really writes-heavy, caching could solve this problem.
* Tried to assure REALLY BASIC idempotency check for events, by introducing `idempotency_key` containing of key domain fields of event (`matchId`, `type`, `minute`, `second`).
* Added message broker & realtime server (RabbitMQ and simple Node websockets server) to show how I would plan an architecture which is flexible for multiple clients and deeply observable.
* Removed file storage persistence and used sqlite instead to be able to use indexes.
* Refactored `EventHandler` for more flexible but still simple code.
* Refactored API layer to decouple it from business logic.
* Obviously I could also remove repositories calls from http layer but for a sake of readability in this demo I've ommited another `services 🤡` layer :)
* Updated tests to match new API & infra.
* Added comments in some places for more detailed argumentation.

All of these resulted in the most over-engineered sports stars app which has 2 enpoints, but I hope you will appreciate the effort! Also I firmly belive that this is how things should be done at scale. I did not add caching as it would be a solid overkill in this demo so I decided to just mention it here.

__Here you can find the [docs of new API: REQUESTS.md](/REQUESTS.md)__

### Code refactor & new directories: 
* `/api` - only http api related code. Used `slim` for simplicity & basic boilerplate validation as no validation dependency was really necessary.
* `/db` - simple one-time migration for sqlite, to ensure better persistance layer (no problems with file locks, better performance and ability to use db constraints / indexes)
* `/mq`- infra adapter for publisher service interface required by event handlers
* `/persistence`- infra adapter for event repository interface
* `/public` - `index.php` controller with app bootstrap/DI bidings and `demo.html` for realtime client features showcase
* `/src` - isolated core business logic, zero direct calls to infra nor http layer. 
* `/ws` - simple code for node websocket server, necessary for my realtime solution

### Core business requirements
- [x] System accurately logs and updates statistics upon receiving a **goal** event, including details such as scorer, assisting player, team, minute, and match ID.
- [x] System accurately logs and updates records upon receiving a **foul** event, including details such as player at fault, affected player, team, match ID, and precise time of the foul.
- [x] All event data is permanently stored and retrievable
- [x] Relevant statistics are calculated and maintained for both event types
- [x] Clients receive information about all events in real-time
- [x] Data integrity is maintained at all times
- [x] Historical data is preserved and accessible
- [ ] System can handle high volume of events

### Client communication requirements
- [x] All clients receive event notifications
- [x] Information is delivered in a timely manner
- [x] Communication is reliable and consistent

## Use of AI

I've used `Codex` for:
- Prettifying UI of html demo.
- Searching for unused code, typo-s / finding stupic bugs.
- Fixing my boilerplate validation.

I've used `ChatGPT`
- Some advices with docker.
- Advices with AMQP.
- Advices with `ws` js code.

I also often use `Copilot` for better autocompletion.

Basically everything I came up with and wrote in PHP comes from human brain, but I did not hesitate to use AI to things like simple JS/HTML or Docker config details/tweaking.

## Setup

### Requirements

- Docker
- Docker Compose

### Build

1. Build and run the container:
```bash
docker compose up --build -d
```

2. Run install:
```bash
docker exec -it football_events_app composer install
```

3. The demo will be available at: `http://localhost:8000`

4. RabbitMQ dashboard at: `http://localhost:15672/`
- Login: `guest`
- Password: `guest`
