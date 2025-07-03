<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\DemandeDevis;

class WorkflowTimeline extends Component
{
    public DemandeDevis $demande;
    public bool $showDetails = false;

    public function mount(DemandeDevis $demande)
    {
        $this->demande = $demande;
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function approveStep()
    {
        if (!$this->canUserTakeAction()) {
            session()->flash('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            return;
        }

        try {
            $this->demande->approve(auth()->user(), 'Approuvé via timeline');
            session()->flash('success', 'Demande approuvée avec succès!');
            $this->dispatch('workflow-updated');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function rejectStep()
    {
        if (!$this->canUserTakeAction()) {
            session()->flash('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            return;
        }

        $this->dispatch('open-reject-modal');
    }

    private function canUserTakeAction(): bool
    {
        $currentStep = $this->demande->getCurrentApprovalStep();
        if (!$currentStep) return false;

        return match($currentStep) {
            'responsable-budget' => auth()->user()->hasRole('responsable-budget'),
            'service-achat' => auth()->user()->hasRole('service-achat'),
            'reception-livraison' => auth()->user()->hasRole('service-demandeur') &&
                                   auth()->user()->service_id === $this->demande->service_demandeur_id,
            default => false
        };
    }

    public function getTimelineEvents()
    {
        $events = collect([
            [
                'step' => 'creation',
                'title' => 'Demande créée',
                'description' => "Par {$this->demande->serviceDemandeur->nom}",
                'date' => $this->demande->created_at,
                'status' => 'completed',
                'icon' => 'heroicon-o-plus-circle',
                'color' => 'success'
            ]
        ]);

        if ($this->demande->approvalsHistory) {
            foreach ($this->demande->approvalsHistory as $approval) {
                $events->push([
                    'step' => $approval->step,
                    'title' => $this->getStepTitle($approval->step),
                    'description' => $approval->status === 'approved' ?
                        "Validé par {$approval->user->name}" :
                        "Rejeté par {$approval->user->name}",
                    'date' => $approval->created_at,
                    'status' => $approval->status,
                    'icon' => $approval->status === 'approved' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
                    'color' => $approval->status === 'approved' ? 'success' : 'danger',
                    'comment' => $approval->comment
                ]);
            }
        }

        return $events;
    }

    private function getStepTitle($step): string
    {
        return match($step) {
            'responsable-budget' => 'Validation budgétaire',
            'service-achat' => 'Validation achat',
            'reception-livraison' => 'Contrôle réception',
            default => ucfirst($step)
        };
    }

    public function render()
    {
        return view('livewire.workflow-timeline', [
            'timelineEvents' => $this->getTimelineEvents(),
            'canUserAct' => $this->canUserTakeAction(),
            'currentStep' => $this->demande->getCurrentApprovalStep()
        ]);
    }
}
