<?php
/**
 * PEGASUS ERP — central helper for building approval-stepper data
 * for any entity that follows the standard 2- or 3-step approval pattern.
 */

class ApprovalFlow
{
    /** Common 2-step master flow: DRAFT/PENDING_MANAGER/PENDING_CEO/APPROVED. */
    public static function masterStepper(array $row): array
    {
        $steps = [
            [
                'key'   => 'DRAFT',
                'label' => __('flow_drafted'),
                'at'    => $row['submitted_at'] ?? ($row['created_at'] ?? null),
                'by'    => $row['created_by_email'] ?? null,
            ],
            [
                'key'   => 'PENDING_MANAGER',
                'label' => __('flow_manager_approval'),
                'at'    => $row['manager_approved_at'] ?? null,
                'by'    => $row['manager_approved_by_email'] ?? null,
            ],
            [
                'key'   => 'PENDING_CEO',
                'label' => __('flow_ceo_approval'),
                'at'    => $row['ceo_approved_at'] ?? null,
                'by'    => $row['ceo_approved_by_email'] ?? null,
            ],
            [
                'key'   => 'APPROVED',
                'label' => __('flow_active'),
                'at'    => (($row['approval_status'] ?? '') === 'APPROVED'
                            ? ($row['ceo_approved_at'] ?? $row['manager_approved_at'] ?? null) : null),
                'by'    => null,
            ],
        ];
        return [
            'current_status' => $row['approval_status'] ?? 'DRAFT',
            'rejected'       => ($row['approval_status'] ?? '') === 'REJECTED',
            'reject_reason'  => $row['rejection_reason'] ?? null,
            'steps'          => $steps,
        ];
    }

    /** PR 3-step flow. */
    public static function prStepper(array $pr): array
    {
        $steps = [
            ['key'=>'DRAFT',           'label'=>__('flow_drafted'),
             'at'=>$pr['created_at'] ?? null, 'by'=>$pr['requester_name_jp'] ?? null],
            ['key'=>'QUOTES_PENDING',  'label'=>__('flow_3quotes_collection'),
             'at'=>$pr['purchasing_approved_at'] ?? null, 'by'=>$pr['purchasing_approver_email'] ?? null],
            ['key'=>'PENDING_MANAGER', 'label'=>__('flow_manager_approval'),
             'at'=>$pr['manager_approved_at'] ?? null, 'by'=>$pr['manager_approver_email'] ?? null],
            ['key'=>'PENDING_CEO',     'label'=>__('flow_ceo_approval'),
             'at'=>$pr['ceo_approved_at'] ?? null, 'by'=>null],
            ['key'=>'APPROVED',        'label'=>__('flow_completed'),
             'at'=>(in_array($pr['status'] ?? '', ['APPROVED','CONVERTED'], true)
                     ? ($pr['ceo_approved_at'] ?? $pr['manager_approved_at']) : null),
             'by'=>null],
        ];
        return [
            'current_status' => $pr['status'] ?? 'DRAFT',
            'rejected'       => ($pr['status'] ?? '') === 'REJECTED',
            'reject_reason'  => $pr['rejection_reason'] ?? null,
            'steps'          => $steps,
        ];
    }

    /** PO 2-step flow. */
    public static function poStepper(array $po): array
    {
        $steps = [
            ['key'=>'DRAFT',          'label'=>__('flow_drafted'),
             'at'=>$po['created_at'] ?? null, 'by'=>null],
            ['key'=>'PENDING_MANAGER','label'=>__('flow_manager_approval'),
             'at'=>$po['manager_approved_at'] ?? null, 'by'=>null],
            ['key'=>'PENDING_CEO',    'label'=>__('flow_ceo_approval'),
             'at'=>$po['ceo_approved_at'] ?? null, 'by'=>null],
            ['key'=>'APPROVED',       'label'=>__('flow_completed'),
             'at'=>(($po['status'] ?? '') === 'APPROVED' ? ($po['approved_at'] ?? null) : null),
             'by'=>null],
        ];
        return [
            'current_status' => $po['status'] ?? 'DRAFT',
            'rejected'       => ($po['status'] ?? '') === 'REJECTED',
            'reject_reason'  => $po['rejection_reason'] ?? null,
            'steps'          => $steps,
        ];
    }
}
