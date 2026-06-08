export type AgentStatus = 'pending' | 'active' | 'revoked';
export type CheckStatus = 'ok' | 'warning' | 'critical' | 'unknown';
export type CheckName = 'php' | 'mysql' | 'reverb' | 'redis';

export interface Agent {
    id: string;
    name: string;
    hostname: string | null;
    fingerprint: string;
    status: AgentStatus;
    is_online: boolean;
    last_seen_at: string | null;
    check_interval: number;
    config_poll_interval: number;
    config_version: number;
    php_config: PHPConfig;
    mysql_config: MySQLConfig;
    reverb_config: ReverbConfig;
    redis_config: RedisConfig;
    created_at: string;
    report_count?: number;
}

export interface PHPConfig {
    enabled: boolean;
    fpm_socket: string;
    status_url: string;
}

export interface MySQLConfig {
    enabled: boolean;
    dsn: string;
    check_replication: boolean;
}

export interface ReverbConfig {
    enabled: boolean;
    host: string;
    port: number;
}

export interface RedisConfig {
    enabled: boolean;
    addr: string;
    password: string;
    db: number;
}

export interface AgentReport {
    id: number;
    hostname: string;
    reported_at: string;
    status: CheckStatus;
    checks: CheckResult[];
}

export interface CheckResult {
    name: CheckName;
    status: CheckStatus;
    message: string;
    metrics: Record<string, unknown>;
    checked_at: string;
}

export interface AgentTimelineDay {
    date: string;
    slots: Array<CheckStatus | null>;
}

export interface AgentSummary {
    id: string;
    name: string;
    hostname: string | null;
    status: AgentStatus;
    is_online: boolean;
    last_seen_at: string | null;
    last_report: {
        status: CheckStatus;
        checks: Array<{ name: CheckName; status: CheckStatus; message: string }>;
    } | null;
}

export interface DashboardStats {
    total: number;
    active: number;
    pending: number;
    revoked: number;
    online: number;
}
