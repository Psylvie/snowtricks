document.addEventListener('DOMContentLoaded', function () {
    const showMediaButton = document.getElementById('show-media');

    if (showMediaButton) {
        showMediaButton.addEventListener('click', function (event) {
            event.preventDefault();
            const mediaDiv = document.getElementById('trick-media');
            if (mediaDiv) {
                mediaDiv.classList.toggle('d-none');
            }
        });
    }

    const editImageModal = document.getElementById('editImageModal');
    if (editImageModal) {
        editImageModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const pictureId = button.getAttribute('data-picture-id');
            const pictureIdInput = document.getElementById('pictureId');
            pictureIdInput.value = pictureId;
        });
    }

    const videoModal = document.getElementById('editVideoModal');
    if (videoModal) {
        videoModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const videoId = button.getAttribute('data-video-id');
            const videoLink = button.getAttribute('data-video-link');

            const modalVideoId = videoModal.querySelector('#videoId');
            const modalVideoLink = videoModal.querySelector('#newVideoLink');

            modalVideoId.value = videoId;
            modalVideoLink.value = videoLink;
        });
    }

    // Delete Picture
    document.querySelectorAll('.delete-picture-link').forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            const pictureId = this.getAttribute('data-picture-id');
            const url = this.getAttribute('href');
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({delete_picture_id: pictureId})
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erreur lors de la suppression de l\'image.');
                        }
                    });
            }
        });
    });

    // Delete videos
    document.querySelectorAll('.delete-video-link').forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            const videoId = this.getAttribute('data-video-id');
            const url = this.getAttribute('href');
            if (confirm('Êtes-vous sûr de vouloir supprimer cette vidéo ?')) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({delete_video_id: videoId})
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erreur lors de la suppression de la vidéo.');
                        }
                    });
            }
        });
    });

    // Delete main picture
    document.querySelectorAll('.delete-image-main').forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const pictureId = this.getAttribute('data-picture-id');
            const trickSlugElement = document.querySelector('.container.my-5.bg-light');
            const trickSlug = trickSlugElement ? trickSlugElement.dataset.trickSlug : null;
            const url = trickSlug ? `/trick/edit/${trickSlug}` : null;

            if (url && confirm('Êtes-vous sûr de vouloir supprimer cette image principale ?')) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        delete_picture_id: pictureId,
                    }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erreur lors de la suppression de l\'image principale.');
                        }
                    });
            }
        });
    });

    // Added new video fields
    const addVideoButton = document.getElementById("add-video");
    const videoList = document.getElementById("videos-list");

    if (addVideoButton) {
        addVideoButton.addEventListener("click", () => {
            const index = videoList.querySelectorAll("input").length;

            const newVideoElement = document.createElement("div");
            newVideoElement.id = `trick_videos_${index}`;

            const label = document.createElement("label");
            label.setAttribute("for", `trick_videos_${index}_videoLink`);
            label.textContent = "Lien de la vidéo";

            const input = document.createElement("input");
            input.type = "text";
            input.id = `trick_videos_${index}_videoLink`;
            input.name = `trick[videos][${index}][videoLink]`;
            input.classList.add("form-control", "mb-3");

            newVideoElement.appendChild(label);
            newVideoElement.appendChild(input);

            videoList.appendChild(newVideoElement);

            const videoInputs = newVideoElement.querySelectorAll("input");
            videoInputs.forEach((input) => {
                input.style.marginBottom = "10px";
            });

        });
    }
});
