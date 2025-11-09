<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreatedNotification extends Notification /*implements ShouldQueue*/
{
//    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Task $task)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новая задача назначена вам')
            ->greeting('Здравствуйте!')
            ->line('Вам была назначена новая задача: '.$this->task->title)
            ->when($this->task->description, fn (MailMessage $mail) => $mail->line('Описание: '.$this->task->description))
            ->line('Статус: '.$this->task->status->value)
            ->when($this->task->due_date, fn (MailMessage $mail) => $mail->line('Срок выполнения: '.$this->task->due_date->format('d.m.Y')))
            ->line('Проект: '.$this->task->project->name)
            ->line('Спасибо за использование нашего приложения!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_name' => $this->task->project->name,
        ];
    }
}
