<?php

require_once __DIR__ . '/../models/Database.php';

class PhotoRepository {
    private $pdo;

    public function __construct($pdo = null) {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function searchPhotos($userId, $criteria) {
        $sql = "SELECT p.*, a.title as album_title 
                FROM photos p
                JOIN albums a ON p.album_id = a.id
                WHERE 1=1";
        
        $params = [];

        // for the search page

        // managing the scope (my albums vs all accessible albums) for the search page in the filters
        if (isset($criteria['scope']) && $criteria['scope'] === 'owned') {
            $sql .= " AND a.user_id = :user_id";
            $params['user_id'] = $userId;
        } else {
            // scope 'all': user's albums + albums to which they are a contributor + public albums
            $sql .= " AND (a.user_id = :user_id_owner 
                        OR a.visibility = 'public'
                        OR a.id IN (SELECT album_id FROM album_contributors WHERE user_id = :user_id_contrib)
                    )";
            $params['user_id_owner'] = $userId;
            $params['user_id_contrib'] = $userId;
        }

        // filtering by text (photo description or album title)
        if (!empty($criteria['search'])) {
            $sql .= " AND (p.description LIKE :search_text OR a.title LIKE :search_album)";
            $params['search_text'] = '%' . $criteria['search'] . '%';
            $params['search_album'] = '%' . $criteria['search'] . '%';
        }

        // filtering by dates (using shot_at)
        if (!empty($criteria['date_from'])) {
            $sql .= " AND p.shot_at >= :date_from";
            $params['date_from'] = $criteria['date_from'];
        }
        if (!empty($criteria['date_to'])) {
            $sql .= " AND p.shot_at <= :date_to";
            $params['date_to'] = $criteria['date_to'];
        }

        // filtring by tags
        if (!empty($criteria['tags']) && is_array($criteria['tags'])) {
            $tagPlaceholders = [];
            foreach ($criteria['tags'] as $index => $tag) {
                $key = 'tag' . $index;
                $tagPlaceholders[] = ':' . $key;
                $params[$key] = $tag;
            }
            
            // search in photo_tags based on the tag name
            $sql .= " AND p.id IN (
                SELECT pt.photo_id 
                FROM photo_tags pt 
                JOIN tags t ON pt.tag_id = t.id 
                WHERE t.name IN (" . implode(',', $tagPlaceholders) . ")
            )";
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}