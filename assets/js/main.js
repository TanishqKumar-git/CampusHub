// CampusHub front-end JavaScript (vanilla JS, no libraries)

// ---------- Mobile menu ----------
var menuToggle = document.querySelector('.menu-toggle');
if (menuToggle) {
    menuToggle.addEventListener('click', function () {
        document.querySelector('.nav-links').classList.toggle('open');
    });
}

// ---------- Upvote / downvote (AJAX) ----------
var voteButtons = document.querySelectorAll('.vote-btn');

voteButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
        if (!window.CAMPUSHUB.loggedIn) {
            window.location.href = window.CAMPUSHUB.baseUrl + '/auth/login.php';
            return;
        }

        var card = btn.closest('[data-post-id]');
        var postId = card.getAttribute('data-post-id');
        var value = btn.getAttribute('data-value');

        var formData = new FormData();
        formData.append('post_id', postId);
        formData.append('value', value);
        formData.append('csrf_token', window.CAMPUSHUB.csrf);

        fetch(window.CAMPUSHUB.baseUrl + '/api/vote.php', {
            method: 'POST',
            body: formData
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                // Update the score and highlight the active arrow
                card.querySelector('.score').textContent = data.score;
                card.querySelector('.vote-btn.up').classList.toggle('active', data.my_vote === 1);
                card.querySelector('.vote-btn.down').classList.toggle('active', data.my_vote === -1);
            })
            .catch(function () {
                alert('Something went wrong. Please try again.');
            });
    });
});

// ---------- Join / leave community (AJAX) ----------
var joinBtn = document.querySelector('.join-btn');

if (joinBtn) {
    joinBtn.addEventListener('click', function () {
        var formData = new FormData();
        formData.append('community_id', joinBtn.getAttribute('data-community-id'));
        formData.append('csrf_token', window.CAMPUSHUB.csrf);

        fetch(window.CAMPUSHUB.baseUrl + '/api/join_community.php', {
            method: 'POST',
            body: formData
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                joinBtn.textContent = data.joined ? 'Leave' : 'Join';
                joinBtn.classList.toggle('btn-outline', data.joined);
                document.getElementById('member-count').textContent = data.members;
            })
            .catch(function () {
                alert('Something went wrong. Please try again.');
            });
    });
}

// ---------- Hide flash messages after a few seconds ----------
document.querySelectorAll('.flash-success').forEach(function (box) {
    setTimeout(function () { box.style.display = 'none'; }, 4000);
});
