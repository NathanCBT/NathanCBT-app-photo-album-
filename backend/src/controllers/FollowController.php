<?php
session_start();

require_once __DIR__ . '/../repositories/UserRepository.php';

class FollowController {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function getFollowList() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé.']);
            return;
        }

        $currentUserId = (int)$_SESSION['user_id'];
        $targetUserId = isset($_GET['profile_id']) ? (int)$_GET['profile_id'] : $currentUserId;
        $type = $_GET['type'] ?? 'followers';

        try {
            $list = $type === 'following'
                ? $this->userRepository->getFollowingList($targetUserId)
                : $this->userRepository->getFollowersList($targetUserId);

            echo json_encode(['success' => true, 'list' => $list]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function toggleFollow() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé.']);
            return;
        }

        $currentUserId = (int)$_SESSION['user_id'];
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        // accept json payloads or form-encoded / multipart (from FormData)
        if (!is_array($data) || empty($data)) {
            $targetUserId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
        } else {
            $targetUserId = isset($data['target_user_id']) ? (int)$data['target_user_id'] : 0;
        }

        if ($targetUserId === 0 || $targetUserId === $currentUserId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide.']);
            return;
        }

        try {
            $isFollowing = $this->userRepository->isFollowing($currentUserId, $targetUserId);
            if ($isFollowing) {
                $this->userRepository->unfollow($currentUserId, $targetUserId);
                $status = 'follow';
            } else {
                $this->userRepository->follow($currentUserId, $targetUserId);
                $status = 'unfollow';
            }

            $newFollowersCount = $this->userRepository->getFollowersCount($targetUserId);
            echo json_encode(['success' => true, 'status' => $status, 'followers_count' => $newFollowersCount]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}