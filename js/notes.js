// notes.js

requireAuth();

let allNotes = [];

document.addEventListener(
    "DOMContentLoaded",
    async () => {

        setupEvents();

        await logVisit();

        await loadNotes();

    }
);

function setupEvents() {

    document
        .getElementById(
            "createNoteBtn"
        )
        ?.addEventListener(
            "click",
            openCreateModal
        );

    document
        .getElementById(
            "closeNoteModal"
        )
        ?.addEventListener(
            "click",
            closeModal
        );

    document
        .getElementById(
            "noteForm"
        )
        ?.addEventListener(
            "submit",
            saveNote
        );

    document
        .getElementById(
            "searchNote"
        )
        ?.addEventListener(
            "input",
            filterNotes
        );

    document
        .getElementById(
            "pinFilter"
        )
        ?.addEventListener(
            "change",
            filterNotes
        );

}

async function loadNotes() {

    try {

        allNotes =
            await apiRequest(
                "/notes"
            );

        renderNotes(allNotes);

    } catch (err) {

        console.error(err);

    }

}

function renderNotes(notes) {

    const container =
        document.getElementById(
            "notesContainer"
        );

    if (!container) return;

    container.innerHTML = "";

    if (notes.length === 0) {

        container.innerHTML =
            "<p>No notes found.</p>";

        return;
    }

    notes.forEach(note => {

        container.innerHTML += `

        <div class="note-card">

            <div class="note-header">

                <h2>
                    ${note.title}
                </h2>

                ${
                    note.pinned
                    ?
                    `<span class="pin-badge">
                        📌 Pinned
                    </span>`
                    :
                    ""
                }

            </div>

            <p>
                ${note.content}
            </p>

            <small>
                ${note.createdAt}
            </small>

            <div class="note-actions">

                <button
                    onclick="
                        editNote(
                            ${note.id}
                        )
                    "
                >
                    Edit
                </button>

                <button
                    onclick="
                        togglePin(
                            ${note.id}
                        )
                    "
                >
                    ${
                        note.pinned
                        ? "Unpin"
                        : "Pin"
                    }
                </button>

                <button
                    onclick="
                        deleteNote(
                            ${note.id}
                        )
                    "
                >
                    Delete
                </button>

            </div>

        </div>

        `;

    });

}

function filterNotes() {

    const search =
        document
        .getElementById(
            "searchNote"
        )
        .value
        .toLowerCase();

    const pinFilter =
        document
        .getElementById(
            "pinFilter"
        )
        .value;

    const filtered =
        allNotes.filter(note => {

            const matchesSearch =
                note.title
                    .toLowerCase()
                    .includes(search)
                ||
                note.content
                    .toLowerCase()
                    .includes(search);

            let matchesPin =
                true;

            if (
                pinFilter ===
                "PINNED"
            ) {

                matchesPin =
                    note.pinned;

            }

            if (
                pinFilter ===
                "UNPINNED"
            ) {

                matchesPin =
                    !note.pinned;

            }

            return (
                matchesSearch &&
                matchesPin
            );

        });

    renderNotes(filtered);

}

function openCreateModal() {

    document
        .getElementById(
            "noteForm"
        )
        .reset();

    document
        .getElementById(
            "noteId"
        )
        .value = "";

    document
        .getElementById(
            "noteModalTitle"
        )
        .textContent =
        "Create Note";

    document
        .getElementById(
            "noteModal"
        )
        .classList
        .remove("hidden");

}

function closeModal() {

    document
        .getElementById(
            "noteModal"
        )
        .classList
        .add("hidden");

}

function editNote(id) {

    const note =
        allNotes.find(
            n => n.id === id
        );

    if (!note) return;

    document
        .getElementById(
            "noteId"
        )
        .value =
        note.id;

    document
        .getElementById(
            "noteTitle"
        )
        .value =
        note.title;

    document
        .getElementById(
            "noteContent"
        )
        .value =
        note.content;

    document
        .getElementById(
            "notePinned"
        )
        .checked =
        note.pinned;

    document
        .getElementById(
            "noteModalTitle"
        )
        .textContent =
        "Edit Note";

    document
        .getElementById(
            "noteModal"
        )
        .classList
        .remove("hidden");

}

async function saveNote(e) {

    e.preventDefault();

    const noteId =
        document
        .getElementById(
            "noteId"
        )
        .value;

    const noteData = {

        title:
            document
            .getElementById(
                "noteTitle"
            )
            .value,

        content:
            document
            .getElementById(
                "noteContent"
            )
            .value,

        pinned:
            document
            .getElementById(
                "notePinned"
            )
            .checked

    };

    if (noteId) {

        const note =
            allNotes.find(
                n =>
                n.id ==
                noteId
            );

        Object.assign(
            note,
            noteData
        );

    } else {

        allNotes.push({

            id:
                Date.now(),

            ...noteData,

            createdAt:
                new Date()
                .toLocaleDateString()

        });

    }

    await apiRequest(
        "/notes",
        {
            method:"POST",
            body:JSON.stringify({
                title: noteData.title,
                content: noteData.content,
                pinned: noteData.pinned,
            })
        }
    )

    renderNotes(allNotes);

    closeModal();

}

function togglePin(id) {

    const note =
        allNotes.find(
            n => n.id === id
        );

    if (!note) return;

    note.pinned =
        !note.pinned;

    renderNotes(allNotes);

}

function deleteNote(id) {

    const confirmDelete =
        confirm(
            "Delete note?"
        );

    if (!confirmDelete)
        return;

    allNotes =
        allNotes.filter(
            note =>
            note.id !== id
        );

    renderNotes(allNotes);

}

async function logVisit() {

    await apiRequest(
        "/visits/log",
        {
            method:"POST",
            body:JSON.stringify({
                action:
                "OPEN_NOTES"
            })
        }
    );

}