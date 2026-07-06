# Fever code challenge

Welcome! We're thrilled to have you at this stage of the process. This challenge is designed to give us insight into your coding approach and problem-solving skills. It’s a simplified example of real-world scenarios we handle daily at Fever.

## About Fever

At Fever we work to bring experiences to people. We have a marketplace of plans from different providers that are curated and then consumed by multiple applications. We work hard to expand the range of experiences we offer to our customers. Consequently, we are continuously looking for new providers with great plans to integrate in our platforms. 

## The challenge

Your task is to develop a microservice that integrates plans from an external provider into the Fever marketplace.

Even if this is just a disposable test, imagine that somebody will pick up this code and maintain it in the future. It will evolve new features will be added, existing ones adapted, and unnecessary functionalities removed. Writing clean, scalable, and maintainable code is crucial for ensuring the sustainability of any project.

> [!TIP]
> This should be conceived as a long-term project, not just one-off code.

The external provider exposes an endpoint: https://provider.code-challenge.feverup.com/api/events

This API returns a list of available plans in XML format. Plans that are no longer available will not be included in future responses. Here are three example responses over consecutive API calls:

- [Response 1](https://gist.githubusercontent.com/acalvotech/55223c0e5c55baa33086e2383badba64/raw/1cab82e2d1f3adc8d3b3dace0a409844bed698f0/response_1.xml)
- [Response 2](https://gist.githubusercontent.com/acalvotech/d9c6fc5a5920bf741638d6179c8c07ed/raw/2b4ca961f05b2eebc0682f21357d37ac0eb5c80a/response_2.xml)
- [Response 3](https://gist.githubusercontent.com/acalvotech/7c107daacfd05f32c1c1bcd7209d85ef/raw/ea4c4c8d2b7ccf2ae2be153d45353fb7187f5236/response_3.xml)

> [!WARNING]
> The API endpoint has been designed with real-world conditions in mind, where network requests don’t always behave ideally. Your solution should demonstrate how you handle various scenarios that could occur in production environments. **Don’t assume the API endpoint will always respond successfully and with low latency.**

## Your Task

You need to **develop and expose a single endpoint**:

- **API Spec:** [SwaggerHub Reference](https://app.swaggerhub.com/apis-docs/luis-pintado-feverup/backend-test/1.0.0)
- The endpoint should accept `starts_at` and `ends_at` parameters and return only the plans within this time range.
- Plans should be included if they were ever available (with `"sell_mode": "online"`).
- Past plans should be retrievable even if they are no longer present in the provider’s latest response.
- The endpoint must be performant, responding in **hundreds of milliseconds**, regardless of the state of other external services. For instance, if the external provider service is down, our search endpoint should still work as usual. Similarly, it should also respond quickly to all requests regardless of the traffic we receive.

## Evaluation criteria

Your solution will be evaluated holistically, with special attention to:

- **Problem-Solution Fit:** How well your solution aligns with the given problem.
- **Adherence to API Spec:** Follow the provided OpenAPI specification.
- **Documentation:** Provide a README explaining design choices and implementation details, additional design schemas will be valued.
- **Makefile:** Include a Makefile with a run target to simplify running the application.
- **Code Quality:** Readability, maintainability, and adherence to best practices.
- **Software Architecture:** Structural design choices and scalability considerations.
- **Efficiency:** Optimize for both resources and time efficiency.

## Guidelines

- We strongly encourage you to **implement the solution in the language you are most comfortable with**, even if it's not Python. We've seen candidates try to adapt to Python for the sake of the challenge, but that often results in lower code quality and doesn't reflect their real strengths.
- The application will be run on a clean machine with almost no dependencies, so make sure your app installs everything it needs to run in a simple way (one or two commands at most). We encourage to implement a docker compose file, but it is not a requirement.
- Feel free to use any libraries, frameworks, or tools that best fit the task.
- Submit your code in the `master` branch of this repository.

### A note on AI usage

Using AI tools (e.g., Cursor, Copilot, ChatGPT, Claude) is allowed. However, **we expect you to fully understand any AI-generated code** in your submission. During the interview, we will ask you to explain your design decisions, trade-offs, and implementation details. If you used AI, please briefly document how in your README.

## Going the extra mile 🚀

To make your solution even stronger, consider:

- **Scalability:** How would you handle a scenario where the provider sends thousands of plans with hundreds of zones per plan?
- **High Traffic:** How would your service respond to 5k-10k requests per second?
- **Optimization Strategies:** How can the system remain performant under heavy load?

You can implement these enhancements in your code or describe your approach in the README.

## Need Help?

If you have any questions, feel free to reach out. We’ll get back to you as soon as possible.

## Feedback

We value your time and effort! Please take a moment to share your thoughts on our process:

[📋 Feedback Form](https://forms.gle/6NdDApby6p3hHsWp8)

Thank you for participating, and good luck! 🎉

## Solution

This solution implements a Laravel-based microservice that exposes a single search endpoint for Fever marketplace events.

The main design decision is to keep the public search endpoint completely independent from the external provider availability. Instead of calling the provider on every API request, the application synchronizes provider data into a local database through an Artisan command. The API then reads only from local persisted data, which allows it to respond quickly even if the external provider is slow, unavailable, or returning invalid responses.

Only plans with `sell_mode="online"` are stored. Once a plan has been synchronized, it remains available in the local database, even if it disappears from future provider responses. This satisfies the requirement that past plans should still be retrievable.

### Design choices

- **Local persistence first**: the API does not depend on the external provider during user requests.
- **Resilient synchronization**: provider failures are handled in the synchronization command and logged without breaking the search endpoint.
- **Historical availability**: synchronized plans are not deleted when they disappear from the provider response.
- **Provider abstraction**: external sources are represented through a contract, making it easier to add more providers in the future.
- **Simple deployment**: Docker Compose and a `Makefile` are provided so the application can be started with a single command.
- **SQLite for the challenge**: SQLite keeps the setup simple and portable. For production, the same model could be moved to PostgreSQL or MySQL with proper indexing.

### Synchronization flow

```mermaid
sequenceDiagram
    participant Command as events:sync
    participant Provider as External Provider
    participant Parser as XML Parser
    participant DB as Database

    Command->>Provider: Fetch XML events
    Provider-->>Command: XML response
    Command->>Parser: Parse base plans and plans
    Parser-->>Command: Normalized event data
    Command->>DB: Upsert online plans
```

### Request flow

```mermaid
sequenceDiagram
    participant Client
    participant API
    participant DB

    Client->>API: GET /api/v1/events/search?starts_at=...&ends_at=...
    API->>API: Validate query parameters
    API->>DB: Search events within date range
    DB-->>API: Matching events
    API-->>Client: JSON response
```

### Implementation details

The synchronization command fetches the XML from the provider, parses all `base_plan` nodes, filters out non-online plans, extracts the relevant plans and zones, calculates minimum and maximum prices, and stores the normalized data in the `events` table.

The search endpoint receives `starts_at` and `ends_at` query parameters and returns the events that match the requested time range using the locally stored data.

This approach makes the API fast and predictable because request latency depends mainly on the database query, not on external network calls.

### Requirements

- Docker
- Docker Compose

The application runs inside Docker, so PHP and Composer do not need to be installed locally.

### Getting started

#### 1. Clone the repository

```bash
git clone git@github.com:FeverCodeChallenge/GuillermoFernandez.git fever
cd fever
```

You can change the destination directory name

#### 2. Deploy the application

```bash
make run
```

Laravel will ask if it should create the SQLite database file. Answer `yes`.

The API will be available at:

```bash
http://localhost:8083
```

".env" file should have this line.

```env
DB_CONNECTION=sqlite
```

#### 3. Populate the database with data from external provider

```bash
docker compose exec php-fpm php artisan events:sync
```

It's possible that this command fails because the external API is not 100% reliable. It can be run all the times you want.

### Running the tests

To run the full test suite:

```bash
docker compose exec php-fpm php artisan test
```

Alternatively, PHPUnit can be executed directly:

```bash
docker compose exec php-fpm ./vendor/bin/phpunit
```

### API endpoints

#### List events
```http
http://localhost:8083/api/v1/events/search?starts_at=2021-07-31T00:00:00Z&ends_at=2021-12-31T21:00:00Z
```
