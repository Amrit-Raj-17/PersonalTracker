requireAuth();

document.addEventListener(
    "DOMContentLoaded",
    async () => {

        loadBasicInfo();

        await loadDashboard();

        await apiRequest(
            "/visits/log",
            {
                method: "POST",

                body: JSON.stringify({

                    action:
                        "OPEN_DASHBOARD"

                })
            }
        );

    }
);

function loadBasicInfo() {

    const name =
        localStorage.getItem("name");

    const welcomeText =
        document.getElementById(
            "welcomeText"
        );

    welcomeText.textContent =
        `Welcome, ${name}`;

    const today =
        new Date();

    document.getElementById(
        "todayDate"
    ).textContent =
        today.toDateString();

}

async function loadDashboard() {

    try {

        const res =
            await apiRequest(
                "/dashboard"
            );
        
        const data = await res.json();

        renderDailyStats(
            data.dailyStats
        );

        renderTasks(
            data.recentTasks
        );

        renderNotes(
            data.pinnedNotes
        );

        renderActivity(
            data.recentActivities
        );

        renderOverview(
            data.taskSummary
        );

    } catch (err) {

        console.error(err);

    }

}

function renderDailyStats(
    daily
) {

    document.getElementById(
        "dailyCompleted"
    ).textContent =
        `${daily.completed} / ${daily.total}`;

    const percentage =
        daily.total === 0
            ? 0
            : (
                daily.completed /
                daily.total
            ) * 100;

    document.getElementById(
        "dailyProgressBar"
    ).style.width =
        `${percentage}%`;

}

function renderOverview(
    tasks
) {

    document.getElementById(
        "taskCount"
    ).textContent =
        tasks.total;

    document.getElementById(
        "completedTasks"
    ).textContent =
        tasks.completed;

    const productivity =
        tasks.total === 0
            ? 0
            : Math.round(
                (
                    tasks.completed /
                    tasks.total
                ) * 100
            );

    document.getElementById(
        "productivity"
    ).textContent =
        `${productivity}%`;

}

function renderNotes(
    notes
) {

    const container =
        document.getElementById(
            "pinnedNotes"
        );

    container.innerHTML = "";

    notes.forEach(note => {

        container.innerHTML += `
            <div class="note-card">

                <h3>
                    ${note.title}
                </h3>

                <p>
                    ${note.content}
                </p>

            </div>
        `;

    });

}

function renderTasks(
    tasks
) {

    const container =
        document.getElementById(
            "dashboardTasks"
        );

    container.innerHTML = "";

    tasks.forEach(task => {

        container.innerHTML += `
            <div class="task-card">

                <h3>
                    ${task.title}
                </h3>

                <div class="progress-bar">

                    <div
                        class="task-progress"
                        style="width:${task.progress}%"
                    >
                    </div>

                </div>

                <small>
                    ${task.progress}%
                </small>

            </div>
        `;

    });

}

function renderActivity(
    activities
) {

    const container =
        document.getElementById(
            "recentActivity"
        );

    container.innerHTML = "";

    activities.forEach(item => {

        container.innerHTML += `
            <div class="activity-item">

                <span>
                    ${item.action}
                </span>

                <small>
                    ${item.createdAt}
                </small>

            </div>
        `;

    });

}
