import type { Component } from 'vue';
import {
    Activity,
    BookOpen,
    ClipboardCheck,
    FileText,
    GraduationCap,
    LayoutDashboard,
    LibraryBig,
    ListChecks,
    NotebookPen,
    School,
    Users,
    Video,
} from 'lucide-vue-next';

export type RuangRole = 'admin' | 'tutor' | 'student';

export interface RuangNavigationItem {
    title: string;
    routeName: string;
    fallbackPage: string;
    icon: Component;
    children?: RuangNavigationItem[];
}

export const roleLabels: Record<RuangRole, string> = {
    admin: 'Administrator',
    tutor: 'Tutor',
    student: 'Siswa',
};

export const roleNavigation: Record<RuangRole, RuangNavigationItem[]> = {
    admin: [
        { title: 'Beranda', routeName: 'dashboard', fallbackPage: 'admin-home', icon: LayoutDashboard },
        { title: 'Akun pengguna', routeName: 'admin.users.index', fallbackPage: 'admin-users', icon: Users },
        { title: 'Kelas', routeName: 'admin.classes.index', fallbackPage: 'admin-classes', icon: School },
        { title: 'Pustaka materi', routeName: 'admin.materials.index', fallbackPage: 'admin-materials', icon: LibraryBig },
    ],
    tutor: [
        { title: 'Beranda', routeName: 'dashboard', fallbackPage: 'tutor-home', icon: LayoutDashboard },
        { title: 'Tasklist harian', routeName: 'tutor.daily-plans.index', fallbackPage: 'tutor-daily-plan', icon: ListChecks },
        {
            title: 'Buat Materi',
            routeName: '',
            fallbackPage: '',
            icon: NotebookPen,
            children: [
                { title: 'Artikel', routeName: 'tutor.materials.article.create', fallbackPage: 'tutor-article-create', icon: FileText },
                { title: 'Video', routeName: 'tutor.materials.video.create', fallbackPage: 'tutor-video-create', icon: Video },
                { title: 'Kuis', routeName: 'tutor.materials.quiz.create', fallbackPage: 'tutor-quiz-create', icon: ClipboardCheck },
            ],
        },
        { title: 'Pustaka materi', routeName: 'tutor.materials.index', fallbackPage: 'tutor-materials', icon: LibraryBig },
        { title: 'Penilaian esai', routeName: 'tutor.essays.index', fallbackPage: 'tutor-essay-review', icon: NotebookPen },
        { title: 'Progres murid', routeName: 'tutor.students.progress', fallbackPage: 'tutor-student-progress', icon: Activity },
    ],
    student: [
        { title: 'Beranda', routeName: 'dashboard', fallbackPage: 'student-home', icon: LayoutDashboard },
        { title: 'Ritme hari ini', routeName: 'student.daily-rhythm', fallbackPage: 'student-daily-rhythm', icon: ListChecks },
        { title: 'Materi', routeName: 'student.materials.index', fallbackPage: 'student-materials', icon: BookOpen },
        { title: 'Kuis & latihan', routeName: 'student.quizzes.index', fallbackPage: 'student-quizzes', icon: GraduationCap },
    ],
};

export function normalizeRuangRole(role: unknown): RuangRole {
    if (typeof role !== 'string') return 'student';

    switch (role.trim().toLowerCase()) {
        case 'admin':
        case 'administrator':
            return 'admin';
        case 'tutor':
        case 'teacher':
            return 'tutor';
        default:
            return 'student';
    }
}
