<?php

namespace CloudCastle\Core\Api\Resources;

final class PaginateResource extends AbstractResource
{
    /**
     * @return array
     */
    public function toArray (): array
    {
        $page = $this->page;
        $total = $this->total;
        $limit = $this->per_page;
        $nextPage = $page + 1;
        $prevPage = $page - 1;
        $lastPage = (int) ceil($total / ($limit ?: 1));
        $from = ($page - 1) * $limit + 1;
        $to = $from + $limit - 1;
        
        if ($limit === 0) {
            $nextPage = null;
            $lastPage = $page;
        }
        
        if ($from <= 1) $from = 1;
        if ($to >= $total) $to = $total;
        if ($to === 0) $to = $total;
        if ($from > $total) $from = $total;
        if ($nextPage > $lastPage) $nextPage = null;
        if ($prevPage < 1) $prevPage = null;
        if ($lastPage == 0) $lastPage = 1;
        
        if ($from === $total && $to === $total && $total === 0) {
            $to = 0;
            $from = 0;
        }
        
        if($limit === 0){
            $limit = $total;
        }
        
        return [
            'current_page' => $page,
            'per_page' => $limit,
            'last_page' => $lastPage,
            'next_page' => $nextPage,
            'prev_page' => $prevPage,
            'total' => $total,
            'from' => $from,
            'to' => $to,
        ];
    }
}